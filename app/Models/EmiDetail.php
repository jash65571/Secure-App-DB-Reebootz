<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmiDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'total_installments',
        'emi_amount',
        'installments_paid',
        'next_emi_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'emi_amount' => 'decimal:2',
        'next_emi_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the sale that owns the EMI details.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the EMI payments for this EMI.
     */
    public function payments()
    {
        return $this->hasMany(EmiPayment::class, 'emi_id');
    }

    /**
     * Check if all EMIs have been paid.
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this->installments_paid >= $this->total_installments;
    }

    /**
     * Calculate remaining amount to be paid.
     *
     * @return float
     */
    public function remainingAmount(): float
    {
        $totalAmount = $this->emi_amount * $this->total_installments;
        $paidAmount = $this->emi_amount * $this->installments_paid;

        return $totalAmount - $paidAmount;
    }
}
