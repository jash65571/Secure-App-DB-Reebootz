<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Warehouse;
use App\Models\Store;
use App\Models\DeviceLog;
use App\Models\QcCheck;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class DeviceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-devices');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Device::query();

        // Apply filters

        // Filter by warehouse
        if (auth()->user()->isWarehouse()) {
            $query->where('warehouse_id', auth()->user()->warehouse_id);
        }
        elseif (auth()->user()->isStore()) {
            $query->where('store_id', auth()->user()->store_id);
        }
        elseif ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by model
        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        // Filter by IMEI
        if ($request->has('imei') && !empty($request->imei)) {
            $query->where(function($q) use ($request) {
                $q->where('imei_1', 'like', '%' . $request->imei . '%')
                  ->orWhere('imei_2', 'like', '%' . $request->imei . '%');
            });
        }

        // Filter by device_id
        if ($request->has('device_id') && !empty($request->device_id)) {
            $query->where('device_id', 'like', '%' . $request->device_id . '%');
        }

        $devices = $query->with(['warehouse', 'store'])->paginate(10);

        // Get filter data
        $warehouses = auth()->user()->isWarehouse()
            ? Warehouse::where('id', auth()->user()->warehouse_id)->get()
            : Warehouse::where('is_active', true)->get();

        $stores = auth()->user()->isStore()
            ? Store::where('id', auth()->user()->store_id)->get()
            : Store::where('is_active', true)->get();

        // Get summary counts
        $totalDevices = Device::count();
        $inWarehouse = Device::where('status', 'in_warehouse')->count();
        $inStore = Device::where('status', 'in_store')->count();
        $sold = Device::where('status', 'sold')->count();
        $returned = Device::where('status', 'returned')->count();

        $summary = [
            'total' => $totalDevices,
            'in_warehouse' => $inWarehouse,
            'in_store' => $inStore,
            'sold' => $sold,
            'returned' => $returned,
        ];

        return view('devices.index', compact('devices', 'warehouses', 'stores', 'summary'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get warehouses based on user role
        if (auth()->user()->isWarehouse()) {
            $warehouses = Warehouse::where('id', auth()->user()->warehouse_id)->get();
        } else {
            $warehouses = Warehouse::where('is_active', true)->get();
        }

        return view('devices.create', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceRequest $request)
    {
        $data = $request->validated();

        // Set initial status and location
        $data['status'] = 'in_warehouse';

        // Set warehouse_id based on user role
        if (auth()->user()->isWarehouse()) {
            $data['warehouse_id'] = auth()->user()->warehouse_id;
        }

        // Create device with device_id
        $device = Device::createWithDeviceId($data);

        // Generate QR code
        $this->generateQrCode($device);

        // Add device log
        $device->addLog(
            'created',
            'Device created and added to warehouse inventory.',
            auth()->id()
        );

        return redirect()->route('devices.index')
            ->with('success', 'Device created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        // Authorize user can view this device
        if (auth()->user()->isWarehouse() && $device->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        if (auth()->user()->isStore() && $device->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $device->load('warehouse', 'store', 'sale', 'sale.emiDetail');

        // Get device logs
        $logs = $device->logs()->with('performer')->orderBy('created_at', 'desc')->get();

        // Get QC checks
        $qcChecks = $device->qcChecks()->with('performer')->orderBy('created_at', 'desc')->get();

        return view('devices.show', compact('device', 'logs', 'qcChecks'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Device $device)
    {
        // Authorize user can edit this device
        if (auth()->user()->isWarehouse() && $device->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        if (auth()->user()->isStore()) {
            abort(403); // Store users can't edit devices
        }

        // Get warehouses based on user role
        if (auth()->user()->isWarehouse()) {
            $warehouses = Warehouse::where('id', auth()->user()->warehouse_id)->get();
        } else {
            $warehouses = Warehouse::where('is_active', true)->get();
        }

        return view('devices.edit', compact('device', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceRequest $request, Device $device)
    {
        // Authorize user can update this device
        if (auth()->user()->isWarehouse() && $device->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        if (auth()->user()->isStore()) {
            abort(403); // Store users can't update devices
        }

        $data = $request->validated();

        // Set warehouse_id based on user role
        if (auth()->user()->isWarehouse()) {
            $data['warehouse_id'] = auth()->user()->warehouse_id;
        }

        $device->update($data);

        // Add device log
        $device->addLog(
            'status_changed',
            'Device details updated.',
            auth()->id()
        );

        return redirect()->route('devices.index')
            ->with('success', 'Device updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        // Only super admin or admin can delete devices
        if (!(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())) {
            abort(403);
        }

        // Only devices in warehouse can be deleted
        if ($device->status !== 'in_warehouse') {
            return back()->with('error', 'Can only delete devices that are in warehouse.');
        }

        // Delete QR code file if exists
        if ($device->qr_code) {
            Storage::disk('public')->delete($device->qr_code);
        }

        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }

    /**
     * Perform QC check on device.
     */
    public function performQc(Request $request, Device $device)
    {
        // Validate request
        $request->validate([
            'check_type' => 'required|in:warehouse,store,customer',
            'passed' => 'required|boolean',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Authorize user can perform QC
        if (auth()->user()->isWarehouse() && $device->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        if (auth()->user()->isStore() && $device->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        // Create QC check
        QcCheck::create([
            'device_id' => $device->id,
            'check_type' => $request->check_type,
            'passed' => $request->passed,
            'remarks' => $request->remarks,
            'performed_by' => auth()->id(),
        ]);

        // Add device log
        $device->addLog(
            'qc_checked',
            'QC check performed: ' . ($request->passed ? 'Passed' : 'Failed') .
            ($request->remarks ? ' - ' . $request->remarks : ''),
            auth()->id()
        );

        return back()->with('success', 'QC check performed successfully.');
    }

    /**
     * Generate QR code for device.
     */
    private function generateQrCode(Device $device)
    {
        // Create QR code with device details
        $qrData = [
            'device_id' => $device->device_id,
            'name' => $device->name,
            'model' => $device->model,
            'imei_1' => $device->imei_1,
            'imei_2' => $device->imei_2,
        ];

        $qrCode = QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate(json_encode($qrData));

        // Save QR code to storage
        $filename = 'qrcodes/' . $device->device_id . '.png';
        Storage::disk('public')->put($filename, $qrCode);

        // Update device with QR code path
        $device->qr_code = $filename;
        $device->save();
    }

    /**
     * Download QR code for device.
     */
    public function downloadQr(Device $device)
    {
        if (!$device->qr_code || !Storage::disk('public')->exists($device->qr_code)) {
            // Regenerate QR code if it doesn't exist
            $this->generateQrCode($device);
        }

        return response()->download(storage_path('app/public/' . $device->qr_code));
    }
}
