<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ForeclosureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes(['register' => false]);

Route::middleware('auth')->group(function() {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::resource('/karyawan', UserController::class, ['names' => 'user']);
    Route::resource('/nasabah', CustomerController::class, ['names' => 'customer']);
    Route::get('/nasabah/{nasabah}/buku-tabungan', [CustomerController::class, 'passbook'])->name('customer.passbook');

    Route::post('/karyawan/cetak', [UserController::class, 'print'])->name('user.print');
    Route::post('/nasabah/cetak', [CustomerController::class, 'print'])->name('customer.print');

    // AJAX Endpoints — session auth (bukan Sanctum)
    Route::get('/api/nasabah/search', [CustomerController::class, 'search'])->name('customer.search');
    Route::get('/api/nasabah/{id}/saldo', [CustomerController::class, 'currentBalanceByDeposit'])->name('customer.balance');
    Route::get('/api/hari-libur/cek', [App\Http\Controllers\HolidayController::class, 'check'])->name('holiday.check');
    Route::get('/api/bunga/rate-aktif', [App\Http\Controllers\InterestRateController::class, 'activeRates'])->name('interest.active');
    Route::post('/api/bunga/posting-bulanan', [HomeController::class, 'postingBulanManual'])->name('bunga.posting-bulanan');

    Route::as('transaction.')->prefix('transaksi')->group(function() {
        Route::get('/simpanan/{simpanan}/kwitansi', [DepositController::class, 'receipt'])->name('deposit.receipt');
        Route::get('/penarikan/{penarikan}/kwitansi', [WithdrawalController::class, 'receipt'])->name('withdrawal.receipt');

        // Cetak Buku Tabungan (Passbook)
        Route::get('/simpanan/{simpanan}/buku-tabungan', [DepositController::class, 'passbook'])->name('deposit.passbook');
        Route::get('/penarikan/{penarikan}/buku-tabungan', [WithdrawalController::class, 'passbook'])->name('withdrawal.passbook');

        // Kwitansi penghapusan — menggunakan plain {id} karena record sudah soft-deleted
        Route::get('/simpanan/{id}/kwitansi-hapus', [DepositController::class, 'destroyReceipt'])->name('deposit.destroy-receipt');
        Route::get('/penarikan/{id}/kwitansi-hapus', [WithdrawalController::class, 'destroyReceipt'])->name('withdrawal.destroy-receipt');

        Route::resource('/simpanan', DepositController::class, ['names' => 'deposit']);
        Route::resource('/penarikan', WithdrawalController::class, ['names' => 'withdrawal']);

        Route::post('/simpanan/cetak', [DepositController::class, 'print'])->name('deposit.print');
        Route::post('/simpanan/update-bunga', [DepositController::class, 'updateBunga'])->name('deposit.update-bunga');
        Route::post('/simpanan/{simpanan}/validasi', [DepositController::class, 'validateTransaction'])->name('deposit.validate');
        Route::get('/simpanan/{simpanan}/cetak-validasi', [DepositController::class, 'printValidation'])->name('deposit.validation-print');
        Route::post('/penarikan/cetak', [WithdrawalController::class, 'print'])->name('withdrawal.print');
        Route::post('/penarikan/{penarikan}/validasi', [WithdrawalController::class, 'validateTransaction'])->name('withdrawal.validate');
        Route::get('/penarikan/{penarikan}/cetak-validasi', [WithdrawalController::class, 'printValidation'])->name('withdrawal.validation-print');
    });

    // Modul 05 & 06 (Hari Libur & Bunga)
    Route::resource('holiday', App\Http\Controllers\HolidayController::class);
    Route::get('interest/history', [App\Http\Controllers\InterestRateController::class, 'history'])->name('interest.history');
    Route::resource('interest', App\Http\Controllers\InterestRateController::class);

    // Modul 09 — Deposito
    Route::post('deposito/update-bunga', [App\Http\Controllers\FixedDepositController::class, 'updateBunga'])->name('fixed-deposit.update-bunga');
    Route::get('deposito/{fixed_deposit}/kwitansi', [App\Http\Controllers\FixedDepositController::class, 'receipt'])->name('fixed-deposit.receipt');
    // Kwitansi penghapusan — menggunakan plain {id} karena record sudah soft-deleted
    Route::get('deposito/{id}/kwitansi-hapus', [App\Http\Controllers\FixedDepositController::class, 'destroyReceipt'])->name('fixed-deposit.destroy-receipt');
    Route::resource('deposito', App\Http\Controllers\FixedDepositController::class, ['names' => 'fixed-deposit'])->except(['edit', 'update']);
    Route::get('deposito/{fixed_deposit}/perpanjang', [App\Http\Controllers\FixedDepositController::class, 'extendForm'])->name('fixed-deposit.extend.form');
    Route::post('deposito/{fixed_deposit}/perpanjang', [App\Http\Controllers\FixedDepositController::class, 'extend'])->name('fixed-deposit.extend');
    Route::get('deposito/{fixed_deposit}/cairkan', [App\Http\Controllers\FixedDepositController::class, 'liquidateForm'])->name('fixed-deposit.liquidate.form');
    Route::post('deposito/{fixed_deposit}/cairkan', [App\Http\Controllers\FixedDepositController::class, 'liquidate'])->name('fixed-deposit.liquidate');
    Route::post('deposito/cetak', [App\Http\Controllers\FixedDepositController::class, 'print'])->name('fixed-deposit.print');
    Route::post('deposito/{fixed_deposit}/validasi', [App\Http\Controllers\FixedDepositController::class, 'validateTransaction'])->name('fixed-deposit.validate');
    Route::get('deposito/{fixed_deposit}/cetak-validasi', [App\Http\Controllers\FixedDepositController::class, 'printValidation'])->name('fixed-deposit.validation-print');

    // Laporan
    Route::get('/laporan/harian', [App\Http\Controllers\ReportController::class, 'dailyTransaction'])->name('report.daily');
    Route::post('/laporan/harian/cetak', [App\Http\Controllers\ReportController::class, 'dailyTransactionPrint'])->name('report.daily.print');
    Route::get('/laporan/rekap-simpanan', [App\Http\Controllers\ReportController::class, 'savingsRecap'])->name('report.savings-recap');
    Route::post('/laporan/rekap-simpanan/cetak', [App\Http\Controllers\ReportController::class, 'savingsRecapPrint'])->name('report.savings-recap.print');
    Route::get('/laporan/rekap-deposito', [App\Http\Controllers\ReportController::class, 'depositRecap'])->name('report.deposit-recap');
    Route::post('/laporan/rekap-deposito/cetak', [App\Http\Controllers\ReportController::class, 'depositRecapPrint'])->name('report.deposit-recap.print');

    // Utility — Trash
    Route::get('/utility/trash', [App\Http\Controllers\TrashController::class, 'index'])->name('trash.index');
    Route::post('/utility/trash/{module}/{id}/restore', [App\Http\Controllers\TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/utility/trash/{module}/{id}', [App\Http\Controllers\TrashController::class, 'forceDelete'])->name('trash.force-delete');

    Route::as('collection.')->prefix('kolektor')->group(function() {
        Route::resource('/nasabah-bermasalah', VisitController::class, ['names' => 'visit']);
        Route::resource('/penarikan-jaminan', ForeclosureController::class, ['names' => 'foreclosure']);

        Route::post('/nasabah-bermasalah/cetak', [VisitController::class, 'print'])->name('visit.print');
        Route::post('/penarikan-jaminan/cetak', [ForeclosureController::class, 'print'])->name('foreclosure.print');
    });

    Route::get('/pengaturan', [HomeController::class, 'profile'])->name('profile.show');
    Route::post('/pengaturan', [HomeController::class, 'update'])->name('profile.update');
    Route::delete('/pengaturan', [HomeController::class, 'truncate'])->name('profile.truncate');
});

