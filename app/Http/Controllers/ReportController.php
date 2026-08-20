<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $pdf->setPaper('A4', 'landscape');

        $filename = $date . '_laporan_transaksi_harian_' . time() . '.pdf';
        return $pdf->download($filename);
    }
}
