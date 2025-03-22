<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Role;
use App\Models\DemandRequest;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-stores');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Store::query();

        // Filter by warehouse if user is warehouse admin
        if (auth()->user()->isWarehouse()) {
            $query->where('warehouse_id', auth()->user()->warehouse_id);
        }
        // Filter by warehouse if provided
        elseif ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by active status
        if ($request->has('status')) {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        $stores = $query->with('warehouse')->paginate(10);

        // Get warehouses for filter
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('stores.index', compact('stores', 'warehouses'));
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

        return view('stores.create', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStoreRequest $request)
    {
        $data = $request->validated();

        $store = Store::create($data);

        // Create store user
        $storeUserRole = Role::where('slug', 'store')->first();

        if ($storeUserRole) {
            // Generate username from store name
            $username = Str::slug($store->name);

            // Check if username exists
            $count = 1;
            $originalUsername = $username;
            while (User::where('email', $username . '@store.com')->exists()) {
                $username = $originalUsername . $count;
                $count++;
            }

            // Generate random password
            $password = Str::random(10);

            // Create user
            $user = User::create([
                'name' => $store->name . ' User',
                'email' => $username . '@store.com',
                'password' => Hash::make($password),
                'role_id' => $storeUserRole->id,
                'store_id' => $store->id,
                'first_login' => true,
            ]);

            // Flash credentials to session
            session()->flash('store_credentials', [
                'email' => $user->email,
                'password' => $password,
            ]);
        }

        return redirect()->route('stores.index')
            ->with('success', 'Store created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        // Authorize user can view this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        $store->load('warehouse', 'users');

        // Get devices summary
        $totalSent = $store->totalDevicesSent();
        $totalSold = $store->totalDevicesSold();
        $totalReturned = $store->totalDevicesReturned();
        $totalInStock = $store->totalDevicesInStock();

        $devicesSummary = [
            'total_sent' => $totalSent,
            'total_sold' => $totalSold,
            'total_returned' => $totalReturned,
            'total_in_stock' => $totalInStock,
        ];

        // Get pending demand requests
        $pendingRequests = DemandRequest::where('store_id', $store->id)
            ->where('status', 'pending')
            ->get();

        // Get store user
        $storeUser = User::where('store_id', $store->id)
            ->whereHas('role', function($q) {
                $q->where('slug', 'store');
            })
            ->first();

        return view('stores.show', compact('store', 'devicesSummary', 'pendingRequests', 'storeUser'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        // Authorize user can edit this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // Get warehouses based on user role
        if (auth()->user()->isWarehouse()) {
            $warehouses = Warehouse::where('id', auth()->user()->warehouse_id)->get();
        } else {
            $warehouses = Warehouse::where('is_active', true)->get();
        }

        return view('stores.edit', compact('store', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        // Authorize user can update this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        $data = $request->validated();

        $store->update($data);

        return redirect()->route('stores.index')
            ->with('success', 'Store updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        // Authorize user can delete this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // Check if store has devices
        $hasDevices = $store->devices()->exists();

        if ($hasDevices) {
            return back()->with('error', 'Cannot delete store because it has devices.');
        }

        $store->delete();

        return redirect()->route('stores.index')
            ->with('success', 'Store deleted successfully.');
    }

    /**
     * Toggle store active status.
     */
    public function toggleStatus(Store $store)
    {
        // Authorize user can toggle this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // If deactivating, check for devices in store
        if ($store->is_active) {
            $hasDevices = $store->devices()->where('status', 'in_store')->exists();

            if ($hasDevices) {
                return back()->with('error', 'Cannot deactivate store because it has devices in stock.');
            }
        }

        $store->is_active = !$store->is_active;
        $store->save();

        $status = $store->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Store {$status} successfully.");
    }

    /**
     * Reset store user password.
     */
    public function resetPassword(Store $store)
    {
        // Authorize user can reset password for this store
        if (auth()->user()->isWarehouse() && $store->warehouse_id !== auth()->user()->warehouse_id) {
            abort(403);
        }

        // Find store user
        $storeUser = User::where('store_id', $store->id)
            ->whereHas('role', function($q) {
                $q->where('slug', 'store');
            })
            ->first();

        if (!$storeUser) {
            return back()->with('error', 'Store user not found.');
        }

        // Generate new password
        $password = Str::random(10);

        // Update user
        $storeUser->password = Hash::make($password);
        $storeUser->first_login = true;
        $storeUser->save();

        // Flash credentials to session
        session()->flash('reset_credentials', [
            'email' => $storeUser->email,
            'password' => $password,
        ]);

        return back()->with('success', 'Store user password reset successfully.');
    }
}
