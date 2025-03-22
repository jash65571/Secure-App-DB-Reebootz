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
        Schema::table('users', function (Blueprint $table) {
            // Add custom columns to existing users table
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->after('id');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }

            if (!Schema::hasColumn('users', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('users', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->after('warehouse_id');
            }

            if (!Schema::hasColumn('users', 'first_login')) {
                $table->boolean('first_login')->default(true)->after('store_id');
            }

            // Add foreign key for role_id if the roles table exists
            if (Schema::hasTable('roles')) {
                $table->foreign('role_id')->references('id')->on('roles');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasTable('roles')) {
                $table->dropForeign(['role_id']);
            }

            // Drop columns
            $columns = ['role_id', 'is_active', 'warehouse_id', 'store_id', 'first_login'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
