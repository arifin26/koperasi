<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Laporan Transaksi Per Hari Ini (Masuk/Keluar)
     */
    public function dailyTransaction(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');

        if ($request->ajax()) {
            $data = Deposit::with('customer')
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function($row) {
                    $createdAt = Carbon::parse($row->created_at);
                    if ($createdAt->format('H:i:s') === '00:00:00' && $row->updated_at) {
                        return Carbon::parse($row->updated_at)->format('H:i');
                    }
                    return $createdAt->format('H:i');
                })
                ->editColumn('customer', function($row) {
                    return $row->customer ? $row->customer->name : '-';
                })
                ->editColumn('type', function($row) {
                    return ucfirst($row->type);
                })
                ->addColumn('masuk', function($row) {
                    if ($row->type !== 'penarikan') {
                        return 'Rp' . number_format($row->amount, 0, ',', '.');
                    }
                    return '-';
                })
                ->addColumn('keluar', function($row) {
                    if ($row->type === 'penarikan') {
                        return 'Rp' . number_format($row->amount, 0, ',', '.');
                    }
                    return '-';
                })
                ->make(true);
        }

        // Summary
        $totalMasuk = Deposit::whereDate('created_at', $date)
            ->where('type', '!=', 'penarikan')
            ->sum('amount');
        $totalKeluar = Deposit::whereDate('created_at', $date)
            ->where('type', 'penarikan')
            ->sum('amount');

        return view('pages.report.daily-transaction', [
            'title' => 'Laporan Transaksi Harian',
            'date' => $date,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
        ]);
    }

    public function dailyTransactionPrint(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');

        $data = Deposit::with('customer')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalMasuk = $data->where('type', '!=', 'penarikan')->sum('amount');
        $totalKeluar = $data->where('type', 'penarikan')->sum('amount');
        $manager = User::where('role', 'manager')->first();

        $pdf = PDF::loadView('pages.report.daily-transaction-print', [
            'title' => 'Laporan Transaksi Harian',
            'user' => auth()->user(),
            'date' => Carbon::parse($date)->isoFormat('dddd, D MMMM Y'),
            'dateRaw' => $date,
            'manager' => $manager,
            'data' => $data,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
        ]);
        $pdf->setPaper('A4', 'portrait');

        $filename = $date . '_laporan_transaksi_harian_' . time() . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Rekap Saldo Simpanan per Nasabah
     */
    public function savingsRecap(Request $request)
    {
        if ($request->ajax()) {
            $statusFilter = $request->status ?? '';
            $startDate = $request->start_date ?? '';
            $endDate = $request->end_date ?? '';

            // Tentukan batas akhir periode tanggal jika ada filter tanggal
            $cutoffDate = null;
            if ($endDate) {
                $cutoffDate = Carbon::parse($endDate)->endOfDay();
            }

            $query = Customer::select('customers.*')
                ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
                ->with('interestRate');

            // Filter nasabah yang memiliki transaksi pada rentang tanggal yang dipilih
            if ($startDate || $endDate) {
                $query->whereHas('deposits', function ($q) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $q->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ]);
                    } elseif ($startDate) {
                        $q->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
                    } elseif ($endDate) {
                        $q->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                    }
                });
            }

            $query->orderBy('name', 'asc');

            // Hitung total dana simpanan nasabah hasil filter aktif
            $filteredCustomers = (clone $query)->get();
            $filteredTotalSaldo = 0;
            foreach ($filteredCustomers as $cust) {
                $depQ = Deposit::where('customer_id', $cust->id);
                if ($cutoffDate) {
                    $depQ->where('created_at', '<=', $cutoffDate);
                }
                $lastDep = $depQ->orderBy('id', 'desc')->first();
                $filteredTotalSaldo += $lastDep ? ($lastDep->current_balance ?? 0) : 0;
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('no_nasabah', fn($row) => $row->number ?? '-')
                ->addColumn('nik', fn($row) => $row->nik ?? '-')
                ->addColumn('nama_nasabah', fn($row) => $row->name)
                ->addColumn('alamat', fn($row) => $row->address ?? '-')
                ->addColumn('phone', fn($row) => $row->phone ?? '-')
                ->addColumn('rate_bunga', fn($row) => ($row->interestRate->rate_percent ?? 0) . '%')
                ->addColumn('total_transaksi', function ($row) use ($startDate, $endDate) {
                    $q = Deposit::where('customer_id', $row->id);
                    if ($startDate && $endDate) {
                        $q->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ]);
                    } elseif ($startDate) {
                        $q->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
                    } elseif ($endDate) {
                        $q->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                    }
                    return $q->count() . ' kali';
                })
                ->addColumn('saldo_simpanan', function ($row) use ($cutoffDate) {
                    $q = Deposit::where('customer_id', $row->id);
                    if ($cutoffDate) {
                        $q->where('created_at', '<=', $cutoffDate);
                    }
                    $lastDeposit = $q->orderBy('id', 'desc')->first();
                    $saldo = $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
                    return 'Rp ' . number_format($saldo, 0, ',', '.');
                })
                ->addColumn('bunga_bulanan', function ($row) use ($cutoffDate) {
                    $q = Deposit::where('customer_id', $row->id);
                    if ($cutoffDate) {
                        $q->where('created_at', '<=', $cutoffDate);
                    }
                    $lastDeposit = $q->orderBy('id', 'desc')->first();
                    $saldo = $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
                    $rate = $row->interestRate->rate_percent ?? 0;
                    $bunga = floor($saldo * ($rate / 100) / 12);
                    return 'Rp ' . number_format($bunga, 0, ',', '.');
                })
                ->addColumn('saldo_raw', function ($row) use ($cutoffDate) {
                    $q = Deposit::where('customer_id', $row->id);
                    if ($cutoffDate) {
                        $q->where('created_at', '<=', $cutoffDate);
                    }
                    $lastDeposit = $q->orderBy('id', 'desc')->first();
                    return $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
                })
                ->addColumn('status_nasabah', function ($row) {
                    if ($row->status == 'blacklist') {
                        return '<span class="badge badge-danger">Blacklist</span>';
                    }
                    return '<span class="badge badge-success">Aktif</span>';
                })
                ->addColumn('aksi', function ($row) {
                    return '<a href="' . route('customer.show', $row) . '" class="btn btn-info btn-xs"><i class="fas fa-eye"></i> Detail</a>';
                })
                ->with('filtered_total_saldo', $filteredTotalSaldo)
                ->with('filtered_total_saldo_formatted', number_format($filteredTotalSaldo, 0, ',', '.'))
                ->rawColumns(['status_nasabah', 'aksi'])
                ->make(true);
        }

        // Summary total saldo semua nasabah
        $totalSaldo = DB::table('deposits as d1')
            ->join(DB::raw('(SELECT customer_id, MAX(id) as max_id FROM deposits WHERE deleted_at IS NULL GROUP BY customer_id) as d2'), function ($join) {
                $join->on('d1.customer_id', '=', 'd2.customer_id')->on('d1.id', '=', 'd2.max_id');
            })
            ->sum('d1.current_balance');

        $totalNasabah = Customer::count();
        $nasabahAktif = Customer::where('status', 'active')->count();

        return view('pages.report.savings-recap', [
            'title' => 'Rekap Simpanan Nasabah',
            'totalSaldo' => $totalSaldo,
            'totalNasabah' => $totalNasabah,
            'nasabahAktif' => $nasabahAktif,
        ]);
    }

    public function savingsRecapPrint(Request $request)
    {
        $statusFilter = $request->status ?? '';
        $startDate = $request->start_date ?? '';
        $endDate = $request->end_date ?? '';

        $cutoffDate = null;
        if ($endDate) {
            $cutoffDate = Carbon::parse($endDate)->endOfDay();
        }

        $query = Customer::select('customers.*')
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->with('interestRate');

        if ($startDate || $endDate) {
            $query->whereHas('deposits', function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                } elseif ($startDate) {
                    $q->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
                } elseif ($endDate) {
                    $q->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                }
            });
        }

        $data = $query->orderBy('name', 'asc')->get();

        $totalSaldo = 0;
        foreach ($data as $customer) {
            $depQ = Deposit::where('customer_id', $customer->id);
            if ($cutoffDate) {
                $depQ->where('created_at', '<=', $cutoffDate);
            }
            $lastDeposit = $depQ->orderBy('id', 'desc')->first();
            $customer->last_deposit = $lastDeposit;

            $txnCountQ = Deposit::where('customer_id', $customer->id);
            if ($startDate && $endDate) {
                $txnCountQ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } elseif ($startDate) {
                $txnCountQ->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
            } elseif ($endDate) {
                $txnCountQ->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            }
            $customer->deposits_count = $txnCountQ->count();

            $totalSaldo += $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
        }

        $periodeLabel = '';
        if ($startDate && $endDate) {
            $periodeLabel = Carbon::parse($startDate)->isoFormat('D MMMM Y') . ' s/d ' . Carbon::parse($endDate)->isoFormat('D MMMM Y');
        } elseif ($startDate) {
            $periodeLabel = 'Sejak ' . Carbon::parse($startDate)->isoFormat('D MMMM Y');
        } elseif ($endDate) {
            $periodeLabel = 'Sampai dengan ' . Carbon::parse($endDate)->isoFormat('D MMMM Y');
        } else {
            $periodeLabel = 'Semua Periode';
        }

        $manager = User::where('role', 'manager')->first();

        $pdf = PDF::loadView('pages.report.savings-recap-print', [
            'title' => 'Laporan Rekap Simpanan Nasabah',
            'user' => auth()->user(),
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data,
            'totalSaldo' => $totalSaldo,
            'statusFilter' => $statusFilter,
            'periodeLabel' => $periodeLabel,
        ]);
        $pdf->setPaper('A4', 'portrait');

        $filename = date('Y-m-d') . '_laporan_rekap_simpanan_' . time() . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Rekap Deposito per Nasabah
     */
    public function depositRecap(Request $request)
    {
        if ($request->ajax()) {
            $statusFilter = $request->status ?? '';
            $bulan = $request->bulan ?? '';
            $tahun = $request->tahun ?? '';

            $data = FixedDeposit::with('customer')
                ->select('fixed_deposits.*')
                ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
                ->when($bulan, fn($q) => $q->whereMonth('start_date', $bulan))
                ->when($tahun, fn($q) => $q->whereYear('start_date', $tahun))
                ->orderBy('start_date', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('no_deposito', fn($row) => $row->number ?? '-')
                ->addColumn('nama_nasabah', fn($row) => $row->customer ? $row->customer->name : '-')
                ->addColumn('no_nasabah', fn($row) => $row->customer ? $row->customer->number : '-')
                ->editColumn('amount', fn($row) => 'Rp ' . number_format($row->amount, 0, ',', '.'))
                ->editColumn('rate_percent', fn($row) => $row->rate_percent . '%')
                ->addColumn('bunga_bulanan', fn($row) => 'Rp ' . number_format($row->monthly_interest, 0, ',', '.'))
                ->editColumn('start_date', fn($row) => Carbon::parse($row->start_date)->isoFormat('DD MMM Y'))
                ->editColumn('maturity_date', fn($row) => Carbon::parse($row->maturity_date)->isoFormat('DD MMM Y'))
                ->addColumn('status_label', fn($row) => $row->status_label)
                ->addColumn('days_until_maturity', function ($row) {
                    // Positif = hari tersisa, negatif = sudah lewat jatuh tempo
                    return (int) Carbon::today()->diffInDays(Carbon::parse($row->maturity_date), false);
                })
                ->addColumn('aksi', function ($row) {
                    return '<a href="' . route('fixed-deposit.show', $row) . '" class="btn btn-info btn-xs"><i class="fas fa-eye"></i> Detail</a>';
                })
                ->rawColumns(['status_label', 'aksi'])
                ->make(true);
        }

        // Summary
        $totalDeposito = FixedDeposit::sum('amount');
        $totalAktif = FixedDeposit::where('status', 'active')->count();
        $totalJatuhTempo = FixedDeposit::where('status', 'matured')->count();
        $totalDicairkan = FixedDeposit::where('status', 'liquidated')->count();
        $nominalAktif = FixedDeposit::where('status', 'active')->sum('amount');

        return view('pages.report.deposit-recap', [
            'title' => 'Rekap Deposito',
            'totalDeposito' => $totalDeposito,
            'totalAktif' => $totalAktif,
            'totalJatuhTempo' => $totalJatuhTempo,
            'totalDicairkan' => $totalDicairkan,
            'nominalAktif' => $nominalAktif,
        ]);
    }

    public function depositRecapPrint(Request $request)
    {
        $statusFilter = $request->status ?? '';
        $bulan = $request->bulan ?? '';
        $tahun = $request->tahun ?? '';

        $data = FixedDeposit::with('customer')
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($bulan, fn($q) => $q->whereMonth('start_date', $bulan))
            ->when($tahun, fn($q) => $q->whereYear('start_date', $tahun))
            ->orderBy('start_date', 'asc')
            ->get();

        $totalNominal = $data->sum('amount');
        $totalBunga = 0;
        foreach ($data as $row) {
            $monthlyInterest = floor($row->amount * ($row->rate_percent / 100) / 12);
            $totalBunga += ($monthlyInterest * $row->tenor_months);
        }

        $periodeLabel = '';
        if ($bulan && $tahun) {
            $periodeLabel = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM Y');
        } elseif ($tahun) {
            $periodeLabel = 'Tahun ' . $tahun;
        } else {
            $periodeLabel = 'Semua Periode';
        }

        $manager = User::where('role', 'manager')->first();

        $pdf = PDF::loadView('pages.report.deposit-recap-print', [
            'title' => 'Laporan Rekap Deposito',
            'user' => auth()->user(),
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data,
            'totalNominal' => $totalNominal,
            'totalBunga' => $totalBunga,
            'statusFilter' => $statusFilter,
            'periodeLabel' => $periodeLabel,
        ]);
        $pdf->setPaper('A4', 'portrait');

        $filename = date('Y-m-d') . '_laporan_rekap_deposito_' . time() . '.pdf';
        return $pdf->download($filename);
    }
}
