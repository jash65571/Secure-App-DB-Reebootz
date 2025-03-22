<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::paginate(10);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pin' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        if ($request->has('create_admin')) {
            $warehouseAdminRole = Role::where('slug', 'warehouse')->first();

            if ($warehouseAdminRole) {
                $username = Str::slug($warehouse->name) . '_admin';

                $count = 1;
                $originalUsername = $username;
                while (User::where('email', $username . '@warehouse.com')->exists()) {
                    $username = $originalUsername . $count;
                    $count++;
                }

                $password = Str::random(10);

                $user = User::create([
                    'name' => $warehouse->name . ' Admin',
                    'email' => $username . '@warehouse.com',
                    'password' => Hash::make($password),
                    'role_id' => $warehouseAdminRole->id,
                    'warehouse_id' => $warehouse->id,
                    'first_login' => true,
                ]);

                session()->flash('admin_credentials', [
                    'email' => $user->email,
                    'password' => $password,
                ]);
            }
        }

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('users', 'stores');

        $devicesTotal = $warehouse->devices()->count();
        $devicesInWarehouse = $warehouse->devices()->where('status', 'in_warehouse')->count();
        $devicesTransferred = $warehouse->devices()->whereIn('status', ['transferred', 'in_store'])->count();
        $devicesSold = $warehouse->devices()->where('status', 'sold')->count();
        $devicesReturned = $warehouse->devices()->where('status', 'returned')->count();

        $devicesSummary = [
            'total' => $devicesTotal,
            'in_warehouse' => $devicesInWarehouse,
            'transferred' => $devicesTransferred,
            'sold' => $devicesSold,
            'returned' => $devicesReturned,
        ];

        return view('warehouses.show', compact('warehouse', 'devicesSummary'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pin' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $hasDevices = $warehouse->devices()->exists();
        $hasStores = $warehouse->stores()->exists();

        if ($hasDevices || $hasStores) {
            return back()->with('error', 'Cannot delete warehouse because it has devices or stores.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        if ($warehouse->is_active) {
            $hasDevices = $warehouse->devices()->where('status', 'in_warehouse')->exists();

            if ($hasDevices) {
                return back()->with('error', 'Cannot deactivate warehouse because it has devices in stock.');
            }
        }

        $warehouse->is_active = !$warehouse->is_active;
        $warehouse->save();

        $status = $warehouse->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Warehouse {$status} successfully.");
    }
}
