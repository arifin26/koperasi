<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Customer;
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

        return view('pages.dashboard', [
            'title' => 'Dashboard',
            'nasabahAktif' => $nasabahAktif,
            'masukHariIni' => $masukHariIni,
            'keluarHariIni' => $keluarHariIni,
            'totalTabungan' => $totalTabungan,
            'chartData' => json_encode($chartData),
            'recentTransactions' => $recentTransactions,
            'maturedDeposits' => $maturedDeposits,
            'lastEngineLog' => $lastEngineLog
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
}
