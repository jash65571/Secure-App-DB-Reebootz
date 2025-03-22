<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\EmiController;
use App\Http\Controllers\DemandRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\PasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'change'])->name('password.change.submit');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('warehouses', WarehouseController::class);
    Route::post('/warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('warehouses.toggle-status');

    Route::resource('stores', StoreController::class);
    Route::post('/stores/{store}/toggle-status', [StoreController::class, 'toggleStatus'])->name('stores.toggle-status');
    Route::post('/stores/{store}/reset-password', [StoreController::class, 'resetPassword'])->name('stores.reset-password');

    Route::resource('devices', DeviceController::class);
    Route::post('/devices/{device}/qc', [DeviceController::class, 'performQc'])->name('devices.qc');
    Route::get('/devices/{device}/qr', [DeviceController::class, 'downloadQr'])->name('devices.qr');

    Route::resource('transfers', TransferController::class);
    Route::post('/transfers/{transfer}/receive', [TransferController::class, 'receive'])->name('transfers.receive');
    Route::post('/transfers/{transfer}/in-transit', [TransferController::class, 'inTransit'])->name('transfers.in-transit');
    Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');

    Route::resource('sales', SaleController::class);
    Route::get('/sales/{sale}/invoice', [SaleController::class, 'downloadInvoice'])->name('sales.invoice');
    Route::post('/sales/{sale}/return', [SaleController::class, 'returnDevice'])->name('sales.return');

    Route::get('/emis', [EmiController::class, 'index'])->name('emis.index');
    Route::get('/emis/{emi}', [EmiController::class, 'show'])->name('emis.show');
    Route::get('/emis/{emi}/payment', [EmiController::class, 'createPayment'])->name('emis.payment.create');
    Route::post('/emis/{emi}/payment', [EmiController::class, 'storePayment'])->name('emis.payment.store');
    Route::post('/emis/{emi}/close', [EmiController::class, 'close'])->name('emis.close');

    Route::resource('demands', DemandRequestController::class);
    Route::post('/demands/{demand}/process', [DemandRequestController::class, 'process'])->name('demands.process');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/transfers', [ReportController::class, 'transfers'])->name('reports.transfers');
    Route::get('/reports/emis', [ReportController::class, 'emis'])->name('reports.emis');
});
