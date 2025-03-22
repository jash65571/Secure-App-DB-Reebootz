<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'store_id',
        'device_id',
        'sold_by',
        'customer_name',
        'customer_email',
        'customer_phone',
        'sale_price',
        'on_emi',
        'sale_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sale_price' => 'decimal:2',
        'on_emi' => 'boolean',
        'sale_date' => 'date',
    ];

    /**
     * Get the store that owns the sale.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the device that was sold.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the user who made the sale.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * Get the EMI details for this sale.
     */
    public function emiDetail()
    {
        return $this->hasOne(EmiDetail::class);
    }

    /**
     * Generate a unique invoice number.
     *
     * @param int $storeId
     * @return string
     */
    public static function generateInvoiceNumber(int $storeId): string
    {
        $store = Store::find($storeId);
        if (!$store) {
            throw new \Exception("Store not found.");
        }

        $prefix = strtoupper(substr($store->name, 0, 3));
        $count = self::where('store_id', $storeId)->count() + 1;
        $date = now()->format('Ymd');

        return $prefix . '-' . $date . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}
