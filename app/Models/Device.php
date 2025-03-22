<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'model',
        'imei_1',
        'imei_2',
        'status',
        'warehouse_id',
        'store_id',
        'purchase_date',
        'on_loan',
        'qr_code',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'on_loan' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function logs()
    {
        return $this->hasMany(DeviceLog::class);
    }

    public function transferItems()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function qcChecks()
    {
        return $this->hasMany(QcCheck::class);
    }

    public static function createWithDeviceId(array $attributes)
    {
        $attributes['device_id'] = self::generateUniqueDeviceId(
            $attributes['model'] ?? 'DEV'
        );

        return self::create($attributes);
    }

    private static function generateUniqueDeviceId(string $model): string
    {
        $prefix = strtoupper(substr($model, 0, 3));
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));

        return $prefix . '-' . $timestamp . '-' . $random;
    }

    public function addLog(string $action, string $description, int $userId)
    {
        return DeviceLog::create([
            'device_id' => $this->id,
            'action' => $action,
            'description' => $description,
            'performed_by' => $userId,
        ]);
    }

    public function hasPendingEmi()
    {
        if (!$this->on_loan) {
            return false;
        }

        $sale = $this->sale;
        if (!$sale || !$sale->on_emi) {
            return false;
        }

        $emi = $sale->emiDetail;
        if (!$emi) {
            return false;
        }

        return $emi->installments_paid < $emi->total_installments;
    }
}
