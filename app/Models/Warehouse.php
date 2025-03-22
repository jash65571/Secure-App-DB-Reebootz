<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'pin',
        'contact_person',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function devicesCount()
    {
        return $this->devices()->where('status', 'in_warehouse')->count();
    }
}
