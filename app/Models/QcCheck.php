<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'check_type',
        'passed',
        'remarks',
        'performed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'passed' => 'boolean',
    ];

    /**
     * Get the device that was checked.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the user who performed the check.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
