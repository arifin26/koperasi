<?php

namespace App\Http\Controllers;

use App\Models\InterestRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class InterestRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager')->except(['index']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = InterestRate::where('is_active', 1)->orderBy('type');
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('type', function($row) {
                    return ucwords(str_replace('_', ' ', $row->type));
                })
                ->editColumn('rate_percent', function($row) {
                    return $row->rate_percent . '%';
                })
                ->editColumn('effective_date', function($row) {
                    return Carbon::parse($row->effective_date)->isoFormat('DD MMMM Y');
                })
                ->make(true);
        }

        return view('pages.interest.index', ['title' => 'Manajemen Rate Bunga']);
    }

    public function history(Request $request)
    {
        if ($request->ajax()) {
            $data = InterestRate::orderBy('effective_date', 'desc')->orderBy('id', 'desc');
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('type', function($row) {
                    return ucwords(str_replace('_', ' ', $row->type));
                })
                ->editColumn('rate_percent', function($row) {
                    return $row->rate_percent . '%';
                })
                ->editColumn('effective_date', function($row) {
                    return Carbon::parse($row->effective_date)->isoFormat('DD MMMM Y');
                })
                ->editColumn('is_active', function($row) {
                    return $row->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>';
                })
                ->rawColumns(['is_active'])
                ->make(true);
        }

        return view('pages.interest.history', ['title' => 'Riwayat Rate Bunga']);
    }

    public function create()
    {
        return view('pages.interest.create', ['title' => 'Tambah Rate Bunga Baru']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:tabungan_sukarela,tabungan_wajib,deposito_3_bulan,deposito_6_bulan,deposito_12_bulan',
            'rate_percent' => 'required|numeric|between:0.01,100',
            'effective_date' => 'required|date|after_or_equal:today',
        ]);

        DB::transaction(function () use ($request) {
            // Nonaktifkan rate lama
            InterestRate::where('type', $request->type)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);

            // Insert rate baru
            InterestRate::create([
                'type' => $request->type,
                'rate_percent' => $request->rate_percent,
                'effective_date' => $request->effective_date,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'is_active' => 1
            ]);
        });

        return redirect()->route('interest.index')->with('success', 'Rate Bunga berhasil ditambahkan dan diaktifkan.');
    }

    // API ENDPOINT
    public function activeRates()
    {
        $rates = InterestRate::where('is_active', 1)
            ->get(['type', 'rate_percent', 'effective_date', 'is_active']);

        return response()->json([
            'status' => 'success',
            'data' => $rates
        ]);
    }
}
