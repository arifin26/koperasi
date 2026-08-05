<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/nasabah/search', [CustomerController::class, 'search'])->name('customer.search');
    Route::get('/nasabah/{id}/saldo', [CustomerController::class, 'currentBalanceByDeposit'])->name('customer.balance');

    // Modul 05 & 06
    Route::get('/hari-libur/cek', [\App\Http\Controllers\HolidayController::class, 'check'])->name('holiday.check');
    Route::get('/bunga/rate-aktif', [\App\Http\Controllers\InterestRateController::class, 'activeRates'])->name('interest.active');
});
