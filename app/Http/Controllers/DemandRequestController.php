<?php

namespace App\Http\Controllers;

use App\Models\DemandRequest;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDemandRequestRequest;

class DemandRequestController extends Controller
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
        $query = DemandRequest::query();

        // Apply filters based on user role
        if (auth()->user()->isWarehouse()) {
            $query->whereHas('store', function($q) {
                $q->where('warehouse_id', auth()->user()->warehouse_id);
            });
        } elseif (auth()->user()->isStore()) {
            $query->where('store_id', auth()->user()->store_id);
        }

        // Apply other filters
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        $demandRequests = $query->with(['store.warehouse', 'requester', 'processor'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get filter data
        $stores = auth()->user()->isStore()
            ? Store::where('id', auth()->user()->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('demands.index', compact('demandRequests', 'stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only store users can create demand requests
        if (!auth()->user()->isStore()) {
            abort(403);
        }

        $store = Store::find(auth()->user()->store_id);

        return view('demands.create', compact('store'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDemandRequestRequest $request)
    {
        // Only store users can create demand requests
        if (!auth()->user()->isStore()) {
            abort(403);
        }

        $data = $request->validated();
        $data['store_id'] = auth()->user()->store_id;
        $data['requested_by'] = auth()->id();
        $data['status'] = 'pending';

        DemandRequest::create($data);

        return redirect()->route('demands.index')
            ->with('success', 'Demand request created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DemandRequest $demand)
    {
        // Authorize user can view this demand request
        if (auth()->user()->isWarehouse()) {
            $store = $demand->store;
            if (!$store || $store->warehouse_id !== auth()->user()->warehouse_id) {
                abort(403);
            }
        } elseif (auth()->user()->isStore() && $demand->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $demand->load(['store.warehouse', 'requester', 'processor']);

        return view('demands.show', compact('demand'));
    }

    /**
     * Process demand request (approve, reject, fulfill).
     */
    public function process(Request $request, DemandRequest $demand)
    {
        // Only warehouse users or admins can process demand requests
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isAdmin() && !auth()->user()->isWarehouse()) {
            abort(403);
        }

        // Authorize warehouse user can process this demand request
        if (auth()->user()->isWarehouse()) {
            $store = $demand->store;
            if (!$store || $store->warehouse_id !== auth()->user()->warehouse_id) {
                abort(403);
            }
        }

        // Validate request
        $request->validate([
            'status' => 'required|in:approved,rejected,fulfilled',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Check if demand request is already processed
        if ($demand->status !== 'pending') {
            return back()->with('error', 'Demand request has already been processed.');
        }

        // Update demand request
        $demand->status = $request->status;
        $demand->remarks = $request->remarks;
        $demand->processed_by = auth()->id();
        $demand->save();

        return back()->with('success', 'Demand request processed successfully.');
    }
}
