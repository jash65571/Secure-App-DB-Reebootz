<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse_id',
        'store_id',
        'initiated_by',
        'received_by',
        'status',
        'transfer_date',
        'received_date',
        'notes',
        'qc_passed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transfer_date' => 'date',
        'received_date' => 'date',
        'qc_passed' => 'boolean',
    ];

    /**
     * Get the warehouse that owns the transfer.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the store that owns the transfer.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user who initiated the transfer.
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the user who received the transfer.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the items for this transfer.
     */
    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }
}
