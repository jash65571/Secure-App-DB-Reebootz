<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // Auto-generated unique identifier
            $table->string('name');
            $table->string('model');
            $table->string('imei_1');
            $table->string('imei_2')->nullable();
            $table->enum('status', ['in_warehouse', 'transferred', 'in_store', 'sold', 'returned', 'inactive']);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->foreign('store_id')->references('id')->on('stores');
            $table->date('purchase_date')->nullable();
            $table->boolean('on_loan')->default(false);
            $table->string('qr_code')->nullable(); // Path to stored QR code image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
