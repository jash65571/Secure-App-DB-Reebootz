@extends('layouts.app')

@section('title', 'Warehouses')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Warehouses Management</h3>
                    <a href="{{ route('warehouses.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Create New Warehouse
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Person</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($warehouses as $warehouse)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $warehouse->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $warehouse->city }}, {{ $warehouse->state }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $warehouse->contact_person }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $warehouse->phone }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($warehouse->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('warehouses.show', $warehouse) }}" class="text-blue-600 hover:text-blue-900 mr-2">View</a>
                                        <a href="{{ route('warehouses.edit', $warehouse) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>

                                        <form action="{{ route('warehouses.toggle-status', $warehouse) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900 mr-2">
                                                {{ $warehouse->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 confirm-delete">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $warehouses->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
