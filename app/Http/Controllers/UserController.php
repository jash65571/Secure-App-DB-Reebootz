<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $warehouses = Warehouse::where('is_active', true)->get();
        $stores = Store::where('is_active', true)->get();

        return view('users.create', compact('roles', 'warehouses', 'stores'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $data['password'] = Hash::make('password');
        $data['first_login'] = true;

        if (!in_array($data['role_id'], [2, 3])) {
            $data['warehouse_id'] = null;
        }

        if ($data['role_id'] != 4) {
            $data['store_id'] = null;
        }

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('role', 'warehouse', 'store');

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $warehouses = Warehouse::where('is_active', true)->get();
        $stores = Store::where('is_active', true)->get();

        return view('users.edit', compact('user', 'roles', 'warehouses', 'stores'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (!in_array($data['role_id'], [2, 3])) {
            $data['warehouse_id'] = null;
        }

        if ($data['role_id'] != 4) {
            $data['store_id'] = null;
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function resetPassword(User $user)
    {
        $user->password = Hash::make('password');
        $user->first_login = true;
        $user->save();

        return back()->with('success', 'Password reset successfully. User will need to change password on next login.');
    }

    public function toggleStatus(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }
}
