<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Customer;
use App\Models\AutoInterestRunLog;
use App\Services\AutoInterestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $today = now()->format('Y-m-d');

        // 1. Widget Stats
        $nasabahAktif = \App\Models\Customer::where('status', 'active')->count();

        $masukHariIni = \App\Models\Deposit::whereDate('created_at', $today)
                            ->where('type', '!=', 'penarikan')
                            ->sum('amount');

        $keluarHariIni = \App\Models\Deposit::whereDate('created_at', $today)
                            ->where('type', 'penarikan')
                            ->sum('amount');

        $totalTabungan = \App\Models\Deposit::whereIn('id', function($query) {
                                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                                      ->from('deposits')
                                      ->whereNull('deleted_at')
                                      ->groupBy('customer_id');
                            })->sum('current_balance');

        // 2. Chart 7 Hari Terakhir (Dioptimasi dari 14 query menjadi 1 query agregasi)
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $dailyStats = \App\Models\Deposit::selectRaw("
                DATE(created_at) as txn_date,
                SUM(CASE WHEN type != 'penarikan' THEN amount ELSE 0 END) as masuk,
                SUM(CASE WHEN type = 'penarikan' THEN amount ELSE 0 END) as keluar
            ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('txn_date');

        $chartData = [
            'labels' => [],
            'masuk' => [],
            'keluar' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData['labels'][] = now()->subDays($i)->isoFormat('DD MMM');
            $chartData['masuk'][] = (int) ($dailyStats[$date]->masuk ?? 0);
            $chartData['keluar'][] = (int) ($dailyStats[$date]->keluar ?? 0);
        }

        // 3. 5 Transaksi Terakhir
        $recentTransactions = \App\Models\Deposit::with('customer')
                                ->latest()
                                ->take(5)
                                ->get();

        // 4. Deposito JT 30 Hari
        $maturedDeposits = \App\Models\FixedDeposit::with('customer')
                                ->where('status', 'active')
                                ->whereBetween('maturity_date', [now(), now()->addDays(30)])
                                ->orderBy('maturity_date')
                                ->get();

        // 5. Engine Bunga Status
        $lastEngineLog = \Illuminate\Support\Facades\DB::table('interest_engine_logs')
                            ->latest()
                            ->first();

        // 6. Auto Interest Posting Status
        $lastMonthPeriod = now()->subMonth()->format('Y-m');
        $autoInterestStatus = AutoInterestRunLog::forPeriod($lastMonthPeriod)->first();
        $lastSuccessfulRun = AutoInterestRunLog::successful()->latest('triggered_at')->first();

        return view('pages.dashboard', [
            'title' => 'Dashboard',
            'nasabahAktif' => $nasabahAktif,
            'masukHariIni' => $masukHariIni,
            'keluarHariIni' => $keluarHariIni,
            'totalTabungan' => $totalTabungan,
            'chartData' => json_encode($chartData),
            'recentTransactions' => $recentTransactions,
            'maturedDeposits' => $maturedDeposits,
            'lastEngineLog' => $lastEngineLog,
            'autoInterestStatus' => $autoInterestStatus,
            'lastSuccessfulRun' => $lastSuccessfulRun,
            'lastMonthPeriod' => $lastMonthPeriod
        ]);
    }

    public function profile()
    {
        return view('pages.profile', [
            'title' => 'Pengaturan',
            'profile' => Auth::user()
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = Auth::user();
            $data = $request->except('photo');
            $data['photo'] = $this->updateImage($request, $user->photo);
            $user->update($data);
            return back()->with('success', 'Berhasil mengupdate profil!');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function truncate()
    {
        if (Auth::user()->role !== 'manager') {
            abort(403, 'Hanya manager yang dapat mereset data.');
        }

        if (app()->environment('production')) {
            abort(403, 'Aksi ini tidak diizinkan di environment production.');
        }

        try {
            Artisan::call('migrate:fresh --seed');
            Auth::logout();
            return back()->with('success', 'Berhasil mereset data!');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Manual trigger for posting bunga bulanan
     */
    public function postingBulanManual(Request $request, AutoInterestService $autoInterestService)
    {
        // Restrict to manager role
        if (Auth::user()->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya manager yang dapat melakukan posting bunga bulanan'
            ], 403);
        }

        try {
            // Get period from request or use last month
            $period = $request->input('period', AutoInterestService::getLastMonthPeriod());

            // Validate period format (Y-m)
            if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format periode tidak valid (gunakan Y-m format)'
                ], 422);
            }

            // Run the posting
            $result = $autoInterestService->runIfNeeded($period, force: true);

            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Posting bunga bulan {$period} berhasil dilakukan",
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Posting bunga gagal dilakukan',
                    'data' => $result
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}

