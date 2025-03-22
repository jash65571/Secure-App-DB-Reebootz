<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'requested_by',
        'model',
        'quantity',
        'status',
        'remarks',
        'processed_by',
    ];

    /**
     * Get the store that owns the demand request.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user who requested the demand.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who processed the demand.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
