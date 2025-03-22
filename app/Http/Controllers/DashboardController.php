<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Sale;
use App\Models\Transfer;
use App\Models\EmiDetail;
use App\Models\DeviceLog;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Store;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();
        $recentActivity = $this->getRecentActivity();

        return view('dashboard', compact('stats', 'recentActivity'));
    }

    private function getStats()
    {
        $stats = [];

        if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) {
            $stats['total_devices'] = Device::count();
            $stats['devices_in_stock'] = Device::whereIn('status', ['in_warehouse', 'in_store'])->count();
            $stats['total_sales'] = Sale::count();
            $stats['active_emis'] = EmiDetail::where('is_active', true)->count();
        } elseif (auth()->user()->isWarehouse()) {
            $stats['total_devices'] = Device::where('warehouse_id', auth()->user()->warehouse_id)->count();
            $stats['devices_in_stock'] = Device::where('warehouse_id', auth()->user()->warehouse_id)
                ->where('status', 'in_warehouse')
                ->count();
            $stats['total_sales'] = Sale::whereHas('store', function($q) {
                $q->where('warehouse_id', auth()->user()->warehouse_id);
            })->count();
            $stats['active_emis'] = EmiDetail::whereHas('sale.store', function($q) {
                $q->where('warehouse_id', auth()->user()->warehouse_id);
            })->where('is_active', true)->count();
        } elseif (auth()->user()->isStore()) {
            $stats['total_devices'] = Device::where('store_id', auth()->user()->store_id)->count();
            $stats['devices_in_stock'] = Device::where('store_id', auth()->user()->store_id)
                ->where('status', 'in_store')
                ->count();
            $stats['total_sales'] = Sale::where('store_id', auth()->user()->store_id)->count();
            $stats['active_emis'] = EmiDetail::whereHas('sale', function($q) {
                $q->where('store_id', auth()->user()->store_id);
            })->where('is_active', true)->count();
        }

        return $stats;
    }

    private function getRecentActivity()
    {
        $recentActivity = [];

        $query = DeviceLog::with(['device', 'performer']);

        if (auth()->user()->isWarehouse()) {
            $query->whereHas('device', function($q) {
                $q->where('warehouse_id', auth()->user()->warehouse_id);
            });
        } elseif (auth()->user()->isStore()) {
            $query->whereHas('device', function($q) {
                $q->where('store_id', auth()->user()->store_id);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($logs as $log) {
            $recentActivity[] = [
                'type' => ucfirst($log->action),
                'description' => "Device {$log->device->device_id}: {$log->description}",
                'time' => $log->created_at->diffForHumans(),
            ];
        }

        return $recentActivity;
    }
}
