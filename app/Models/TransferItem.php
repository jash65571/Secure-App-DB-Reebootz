<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transfer_id',
        'device_id',
    ];

    /**
     * Get the transfer that owns the item.
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Get the device that is being transferred.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
