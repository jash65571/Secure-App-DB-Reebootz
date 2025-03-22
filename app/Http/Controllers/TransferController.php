<?php
// File: app/Http/Controllers/TransferController.php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Device;
use App\Models\Warehouse;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTransferRequest;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transfer::query();

        // Apply filters based on user role
        if (auth()->user()->isWarehouse()) {
            $query->where('warehouse_id', auth()->user()->warehouse_id);
        } elseif (auth()->user()->isStore()) {
            $query->where('store_id', auth()->user()->store_id);
        }

        // Apply other filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transfers = $query->with(['warehouse', 'store', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get filter data
        $warehouses = auth()->user()->isWarehouse()
            ? Warehouse::where('id', auth()->user()->warehouse_id)->get()
            : Warehouse::where('is_active', true)->get();

        $stores = auth()->user()->isStore()
            ? Store::where('id', auth()->user()->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('transfers.index', compact('transfers', 'warehouses', 'stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only warehouse users can create transfers
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isWarehouse()) {
            abort(403);
        }

        // Get warehouses based on user role
        if (auth()->user()->isWarehouse()) {
            $warehouses = Warehouse::where('id', auth()->user()->warehouse_id)->get();
            $warehouse_id = auth()->user()->warehouse_id;
        } else {
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouse_id = null;
        }

        // Get stores based on selected warehouse
        if ($warehouse_id) {
            $stores = Store::where('warehouse_id', $warehouse_id)
                ->where('is_active', true)
                ->get();
        } else {
            $stores = [];
        }

        // Get available devices
        if ($warehouse_id) {
            $availableDevices = Device::where('warehouse_id', $warehouse_id)
                ->where('status', 'in_warehouse')
                ->get();
        } else {
            $availableDevices = [];
        }

        return view('transfers.create', compact('warehouses', 'stores', 'availableDevices', 'warehouse_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransferRequest $request)
    {
        // Only warehouse users can create transfers
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isWarehouse()) {
            abort(403);
        }

        // Validate warehouse user can transfer from this warehouse
        if (auth()->user()->isWarehouse() && $request->warehouse_id != auth()->user()->warehouse_id) {
            abort(403);
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Create transfer
            $transfer = Transfer::create([
                'warehouse_id' => $request->warehouse_id,
                'store_id' => $request->store_id,
                'initiated_by' => auth()->id(),
                'status' => 'pending',
                'transfer_date' => now(),
                'notes' => $request->notes,
                'qc_passed' => $request->has('qc_passed'),
            ]);

            // Add devices to transfer
            foreach ($request->device_ids as $deviceId) {
                // Create transfer item
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'device_id' => $deviceId,
                ]);

                // Update device status
                $device = Device::findOrFail($deviceId);
                $device->status = 'transferred';
                $device->save();

                // Add device log
                $device->addLog(
                    'transferred',
                    'Device transferred from warehouse to store (Transfer #' . $transfer->id . ')',
                    auth()->id()
                );
            }

            DB::commit();

            return redirect()->route('transfers.index')
                ->with('success', 'Transfer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transfer $transfer)
    {
        // Authorize user can view this transfer
        if (auth()->user()->isWarehouse() && $transfer->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        if (auth()->user()->isStore() && $transfer->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $transfer->load(['warehouse', 'store', 'initiator', 'receiver', 'items.device']);

        return view('transfers.show', compact('transfer'));
    }

    /**
     * Update the transfer status to received.
     */
    public function receive(Request $request, Transfer $transfer)
    {
        // Only store users or admins can receive transfers
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isStore()) {
            abort(403);
        }

        // Authorize store user can receive this transfer
        if (auth()->user()->isStore() && $transfer->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        // Check if transfer is in a receivable state
        if ($transfer->status !== 'pending' && $transfer->status !== 'in_transit') {
            return back()->with('error', 'Transfer cannot be received in its current status.');
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Update transfer
            $transfer->status = 'received';
            $transfer->received_by = auth()->id();
            $transfer->received_date = now();
            $transfer->save();

            // Update devices
            foreach ($transfer->items as $item) {
                $device = $item->device;
                $device->status = 'in_store';
                $device->store_id = $transfer->store_id;
                $device->save();

                // Add device log
                $device->addLog(
                    'received',
                    'Device received at store (Transfer #' . $transfer->id . ')',
                    auth()->id()
                );
            }

            DB::commit();

            return back()->with('success', 'Transfer received successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Update the transfer status to in_transit.
     */
    public function inTransit(Request $request, Transfer $transfer)
    {
        // Only warehouse users or admins can update transfer status
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isWarehouse()) {
            abort(403);
        }

        // Authorize warehouse user can update this transfer
        if (auth()->user()->isWarehouse() && $transfer->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // Check if transfer is in a pending state
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Transfer must be in pending status to update to in transit.');
        }

        // Update transfer
        $transfer->status = 'in_transit';
        $transfer->save();

        return back()->with('success', 'Transfer status updated to In Transit.');
    }

    /**
     * Cancel the transfer.
     */
    public function cancel(Request $request, Transfer $transfer)
    {
        // Only warehouse users or admins can cancel transfers
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isWarehouse()) {
            abort(403);
        }

        // Authorize warehouse user can cancel this transfer
        if (auth()->user()->isWarehouse() && $transfer->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // Check if transfer is in a cancellable state
        if ($transfer->status !== 'pending' && $transfer->status !== 'in_transit') {
            return back()->with('error', 'Transfer cannot be cancelled in its current status.');
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Update transfer
            $transfer->status = 'cancelled';
            $transfer->save();

            // Update devices
            foreach ($transfer->items as $item) {
                $device = $item->device;
                $device->status = 'in_warehouse';
                $device->store_id = null;
                $device->save();

                // Add device log
                $device->addLog(
                    'status_changed',
                    'Transfer cancelled, device returned to warehouse (Transfer #' . $transfer->id . ')',
                    auth()->id()
                );
            }

            DB::commit();

            return back()->with('success', 'Transfer cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
