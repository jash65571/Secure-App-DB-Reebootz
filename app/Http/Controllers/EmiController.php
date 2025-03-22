<?php

namespace App\Http\Controllers;

use App\Models\EmiDetail;
use App\Models\EmiPayment;
use App\Models\Sale;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmiController extends Controller
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
     * Display a listing of EMIs.
     */
    public function index(Request $request)
    {
        $query = EmiDetail::query();

        // Apply filters based on user role
        if (auth()->user()->isStore()) {
            $query->whereHas('sale', function($q) {
                $q->where('store_id', auth()->user()->store_id);
            });
        }

        // Apply other filters
        if ($request->has('is_active') && $request->is_active !== 'all') {
            $isActive = $request->is_active === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by customer
        if ($request->has('customer') && !empty($request->customer)) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->customer . '%')
                  ->orWhere('customer_email', 'like', '%' . $request->customer . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->customer . '%');
            });
        }

        // Filter by overdue
        if ($request->has('overdue') && $request->overdue === 'yes') {
            $query->where('next_emi_date', '<', now())
                  ->where('is_active', true)
                  ->where('installments_paid', '<', DB::raw('total_installments'));
        }

        $emis = $query->with(['sale.store', 'sale.device'])
            ->orderBy('next_emi_date')
            ->paginate(10);

        return view('emis.index', compact('emis'));
    }

    /**
     * Show a specific EMI detail.
     */
    public function show(EmiDetail $emi)
    {
        // Authorize user can view this EMI
        if (auth()->user()->isStore()) {
            $sale = $emi->sale;
            if (!$sale || $sale->store_id !== auth()->user()->store_id) {
                abort(403);
            }
        }

        $emi->load(['sale.store', 'sale.device', 'sale.seller', 'payments']);

        return view('emis.show', compact('emi'));
    }

    /**
     * Show form to record EMI payment.
     */
    public function createPayment(EmiDetail $emi)
    {
        // Authorize user can record payment
        if (auth()->user()->isStore()) {
            $sale = $emi->sale;
            if (!$sale || $sale->store_id !== auth()->user()->store_id) {
                abort(403);
            }
        }

        // Check if EMI is active
        if (!$emi->is_active) {
            return back()->with('error', 'Cannot record payment for inactive EMI.');
        }

        // Check if all installments are paid
        if ($emi->isFullyPaid()) {
            return back()->with('error', 'All installments are already paid.');
        }

        $emi->load(['sale.store', 'sale.device']);

        return view('emis.payment', compact('emi'));
    }

    /**
     * Record EMI payment.
     */
    public function storePayment(Request $request, EmiDetail $emi)
    {
        // Authorize user can record payment
        if (auth()->user()->isStore()) {
            $sale = $emi->sale;
            if (!$sale || $sale->store_id !== auth()->user()->store_id) {
                abort(403);
            }
        }

        // Validate request
        $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        // Check if EMI is active
        if (!$emi->is_active) {
            return back()->with('error', 'Cannot record payment for inactive EMI.');
        }

        // Check if all installments are paid
        if ($emi->isFullyPaid()) {
            return back()->with('error', 'All installments are already paid.');
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Record payment
            EmiPayment::create([
                'emi_id' => $emi->id,
                'amount_paid' => $request->amount_paid,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
            ]);

            // Update EMI details
            $emi->installments_paid += 1;

            // If this was the last installment
            if ($emi->installments_paid >= $emi->total_installments) {
                $emi->is_active = false;

                // Update device on_loan status
                $device = $emi->sale->device;
                $device->on_loan = false;
                $device->save();

                // Add device log
                $device->addLog(
                    'status_changed',
                    'EMI completed: All payments received for the device.',
                    auth()->id()
                );
            } else {
                // Calculate next EMI date (one month from current next date)
                $nextDate = $emi->next_emi_date;
                $emi->next_emi_date = date('Y-m-d', strtotime('+1 month', strtotime($nextDate)));
            }

            $emi->save();

            DB::commit();

            return redirect()->route('emis.show', $emi)
                ->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Close EMI (mark as inactive).
     */
    public function close(Request $request, EmiDetail $emi)
    {
        // Only admin users can forcefully close EMIs
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        // Validate request
        $request->validate([
            'close_reason' => 'required|string|max:500',
        ]);

        // Check if EMI is already inactive
        if (!$emi->is_active) {
            return back()->with('error', 'EMI is already inactive.');
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Update EMI details
            $emi->is_active = false;
            $emi->save();

            // Update device on_loan status
            $device = $emi->sale->device;
            $device->on_loan = false;
            $device->save();

            // Add device log
            $device->addLog(
                'status_changed',
                'EMI closed administratively. Reason: ' . $request->close_reason,
                auth()->id()
            );

            DB::commit();

            return back()->with('success', 'EMI closed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
