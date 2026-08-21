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
                            ->whereIn('type', ['simpanan', 'bunga'])
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
                            
        // 2. Chart 7 Hari Terakhir
        $chartData = [
            'labels' => [],
            'masuk' => [],
            'keluar' => []
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData['labels'][] = now()->subDays($i)->isoFormat('DD MMM');
            
            $masuk = \App\Models\Deposit::whereDate('created_at', $date)
                        ->whereIn('type', ['simpanan', 'bunga'])
                        ->sum('amount');
            $keluar = \App\Models\Deposit::whereDate('created_at', $date)
                        ->where('type', 'penarikan')
                        ->sum('amount');
                        
            $chartData['masuk'][] = $masuk;
            $chartData['keluar'][] = $keluar;
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
