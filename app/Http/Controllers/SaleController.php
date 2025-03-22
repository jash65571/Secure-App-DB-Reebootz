<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Device;
use App\Models\Store;
use App\Models\EmiDetail;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSaleRequest;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
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
        $query = Sale::query();

        // Apply filters based on user role
        if (auth()->user()->isStore()) {
            $query->where('store_id', auth()->user()->store_id);
        }

        // Apply other filters
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('on_emi') && $request->on_emi !== 'all') {
            $emi = $request->on_emi === 'yes';
            $query->where('on_emi', $emi);
        }

        if ($request->has('customer') && !empty($request->customer)) {
            $query->where(function($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->customer . '%')
                  ->orWhere('customer_email', 'like', '%' . $request->customer . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->customer . '%');
            });
        }

        $sales = $query->with(['store', 'device', 'seller'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get filter data
        $stores = auth()->user()->isStore()
            ? Store::where('id', auth()->user()->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('sales.index', compact('sales', 'stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only store users can create sales
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isStore()) {
            abort(403);
        }

        // Get stores based on user role
        if (auth()->user()->isStore()) {
            $stores = Store::where('id', auth()->user()->store_id)->get();
            $store_id = auth()->user()->store_id;
        } else {
            $stores = Store::where('is_active', true)->get();
            $store_id = null;
        }

        // Get available devices
        if ($store_id) {
            $availableDevices = Device::where('store_id', $store_id)
                ->where('status', 'in_store')
                ->get();
        } else {
            $availableDevices = [];
        }

        return view('sales.create', compact('stores', 'availableDevices', 'store_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        // Only store users can create sales
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isStore()) {
            abort(403);
        }

        // Validate store user can sell from this store
        if (auth()->user()->isStore() && $request->store_id != auth()->user()->store_id) {
            abort(403);
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Get device
            $device = Device::findOrFail($request->device_id);

            // Check device status
            if ($device->status !== 'in_store') {
                return back()->with('error', 'Device is not available for sale.');
            }

            // Check device store
            if ($device->store_id != $request->store_id) {
                return back()->with('error', 'Device does not belong to the selected store.');
            }

            // Generate invoice number
            $invoiceNumber = Sale::generateInvoiceNumber($request->store_id);

            // Create sale
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'store_id' => $request->store_id,
                'device_id' => $request->device_id,
                'sold_by' => auth()->id(),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'sale_price' => $request->sale_price,
                'on_emi' => $request->has('on_emi'),
                'sale_date' => now(),
                'notes' => $request->notes,
            ]);

            // If on EMI, create EMI details
            if ($request->has('on_emi')) {
                EmiDetail::create([
                    'sale_id' => $sale->id,
                    'total_installments' => $request->total_installments,
                    'emi_amount' => $request->emi_amount,
                    'installments_paid' => 0,
                    'next_emi_date' => $request->next_emi_date,
                    'is_active' => true,
                ]);

                // Update device on_loan status
                $device->on_loan = true;
            }

            // Update device status
            $device->status = 'sold';
            $device->save();

            // Add device log
            $device->addLog(
                'sold',
                'Device sold to customer (Invoice #' . $sale->invoice_number . ')',
                auth()->id()
            );

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Sale recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        // Authorize user can view this sale
        if (auth()->user()->isStore() && $sale->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $sale->load(['store', 'device', 'seller', 'emiDetail', 'emiDetail.payments']);

        return view('sales.show', compact('sale'));
    }

    /**
     * Download invoice for the sale.
     */
    public function downloadInvoice(Sale $sale)
    {
        // Authorize user can view this sale
        if (auth()->user()->isStore() && $sale->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $sale->load(['store', 'device', 'seller', 'emiDetail']);

        $pdf = PDF::loadView('sales.invoice', compact('sale'));

        return $pdf->download('invoice-' . $sale->invoice_number . '.pdf');
    }

    /**
     * Return a sold device.
     */
    public function returnDevice(Request $request, Sale $sale)
    {
        // Only store users or admins can process returns
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isStore()) {
            abort(403);
        }

        // Authorize store user can process return for this sale
        if (auth()->user()->isStore() && $sale->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        // Validate request
        $request->validate([
            'return_reason' => 'required|string|max:500',
        ]);

        // Start transaction
        DB::beginTransaction();

        try {
            // Get device
            $device = $sale->device;

            // Check device status
            if ($device->status !== 'sold') {
                return back()->with('error', 'Device is not in sold status.');
            }

            // Check EMI status
            if ($sale->on_emi && $device->hasPendingEmi()) {
                return back()->with('error', 'Device cannot be returned with pending EMI payments.');
            }

            // Update device status
            $device->status = 'returned';
            $device->on_loan = false;
            $device->save();

            // Add device log
            $device->addLog(
                'returned',
                'Device returned by customer. Reason: ' . $request->return_reason,
                auth()->id()
            );

            // If on EMI, update EMI details
            if ($sale->on_emi) {
                $emiDetail = $sale->emiDetail;
                $emiDetail->is_active = false;
                $emiDetail->save();
            }

            DB::commit();

            return back()->with('success', 'Device returned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
