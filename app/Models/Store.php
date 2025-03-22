<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'pin',
        'pan',
        'gst',
        'contact_person',
        'email',
        'phone',
        'warehouse_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function demandRequests()
    {
        return $this->hasMany(DemandRequest::class);
    }

    public function totalDevicesSent()
    {
        return TransferItem::whereHas('transfer', function ($query) {
            $query->where('store_id', $this->id);
        })->count();
    }

    public function totalDevicesSold()
    {
        return $this->sales()->count();
    }

    public function totalDevicesReturned()
    {
        return $this->devices()->where('status', 'returned')->count();
    }

    public function totalDevicesInStock()
    {
        return $this->devices()->where('status', 'in_store')->count();
    }
}
