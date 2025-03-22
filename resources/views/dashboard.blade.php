@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-bold mb-6">Welcome to the Inventory Management System</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Stats Cards -->
                    <div class="bg-blue-100 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-blue-800">Total Devices</h3>
                        <p class="text-3xl font-bold">{{ $stats['total_devices'] ?? 0 }}</p>
                    </div>

                    <div class="bg-green-100 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-green-800">Devices in Stock</h3>
                        <p class="text-3xl font-bold">{{ $stats['devices_in_stock'] ?? 0 }}</p>
                    </div>

                    <div class="bg-yellow-100 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-yellow-800">Total Sales</h3>
                        <p class="text-3xl font-bold">{{ $stats['total_sales'] ?? 0 }}</p>
                    </div>

                    <div class="bg-purple-100 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-purple-800">Active EMIs</h3>
                        <p class="text-3xl font-bold">{{ $stats['active_emis'] ?? 0 }}</p>
                    </div>
                </div>

                <!-- Role-specific Quick Links -->
                <h2 class="text-xl font-semibold mb-4">Quick Links</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                    <a href="{{ route('users.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Manage Users</h3>
                        <p class="text-gray-600">Add, edit, or manage user accounts</p>
                    </a>

                    <a href="{{ route('warehouses.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Manage Warehouses</h3>
                        <p class="text-gray-600">Add, edit, or manage warehouses</p>
                    </a>

                    <a href="{{ route('reports.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Generate Reports</h3>
                        <p class="text-gray-600">Generate various reports for analysis</p>
                    </a>
                    @endif

                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isWarehouse())
                    <a href="{{ route('stores.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Manage Stores</h3>
                        <p class="text-gray-600">Add, edit, or manage retail stores</p>
                    </a>

                    <a href="{{ route('transfers.create') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Create Transfer</h3>
                        <p class="text-gray-600">Transfer devices to retail stores</p>
                    </a>
                    @endif

                    <a href="{{ route('devices.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Manage Devices</h3>
                        <p class="text-gray-600">View, add, or manage inventory devices</p>
                    </a>

                    @if(auth()->user()->isStore())
                    <a href="{{ route('sales.create') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Create Sale</h3>
                        <p class="text-gray-600">Record a new device sale</p>
                    </a>

                    <a href="{{ route('emis.index') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Manage EMIs</h3>
                        <p class="text-gray-600">View and manage EMI payments</p>
                    </a>

                    <a href="{{ route('demands.create') }}" class="block p-4 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h3 class="text-lg font-semibold">Request Devices</h3>
                        <p class="text-gray-600">Create a device demand request</p>
                    </a>
                    @endif
                </div>

                <!-- Recent Activity -->
                <h2 class="text-xl font-semibold mt-8 mb-4">Recent Activity</h2>

                @if(isset($recentActivity) && count($recentActivity) > 0)
                <div class="bg-white border border-gray-200 rounded-lg shadow">
                    <ul class="divide-y divide-gray-200">
                        @foreach($recentActivity as $activity)
                        <li class="p-4">
                            <div class="flex items-start">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $activity['type'] }}</span>
                                <div class="ml-3">
                                    <p class="text-gray-900">{{ $activity['description'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <p class="text-gray-500">No recent activity to display.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
