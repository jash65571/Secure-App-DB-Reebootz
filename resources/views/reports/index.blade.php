@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-bold mb-6">Reports Dashboard</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="{{ route('reports.sales') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h2 class="text-xl font-semibold mb-2">Sales Reports</h2>
                        <p class="text-gray-600">View sales reports, revenue analytics, and customer insights.</p>
                    </a>

                    <a href="{{ route('reports.inventory') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h2 class="text-xl font-semibold mb-2">Inventory Reports</h2>
                        <p class="text-gray-600">Track inventory levels, movements, and device status.</p>
                    </a>

                    <a href="{{ route('reports.transfers') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h2 class="text-xl font-semibold mb-2">Transfer Reports</h2>
                        <p class="text-gray-600">Analyze warehouse to store transfers and fulfilment rates.</p>
                    </a>

                    <a href="{{ route('reports.emis') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50">
                        <h2 class="text-xl font-semibold mb-2">EMI Reports</h2>
                        <p class="text-gray-600">Monitor EMI payments, due dates, and recovery status.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
