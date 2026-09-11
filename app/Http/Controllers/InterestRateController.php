<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterestRateRequest;
use App\Http\Requests\UpdateInterestRateRequest;
use App\Models\InterestRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class InterestRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager')->except(['index', 'activeRates']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InterestRate::query()->orderBy('effective_date', 'desc')->orderBy('id', 'desc');

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('type', function($row) {
                    return $row->type == 'deposito' ? '<span class="badge badge-info">Deposito</span>' : '<span class="badge badge-success">Simpanan</span>';
                })
                ->editColumn('rate_percent', function($row) {
                    return '<strong>' . number_format($row->rate_percent, 2, ',', '.') . '%</strong> p.a.';
                })
                ->editColumn('effective_date', function($row) {
                    return Carbon::parse($row->effective_date)->isoFormat('DD MMMM Y');
                })
                ->editColumn('notes', function($row) {
                    return $row->notes ?: '<span class="text-muted font-italic">-</span>';
                })
                ->editColumn('is_active', function($row) {
                    $defaultBadge = ($row->type === 'simpanan' && $row->is_default) ? '<span class="badge badge-primary ml-1"><i class="fas fa-check"></i> Default</span>' : '';
                    $statusBadge = $row->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>';
                    return $statusBadge . $defaultBadge;
                })
                ->addColumn('action', function($row) {
                    if (auth()->user()->role == 'manager') {
                        return '<a href="'.route('interest.edit', $row).'" class="btn btn-primary btn-xs px-2 mr-1"><i class="fas fa-edit"></i> Edit</a>
                                <form class="d-inline" method="POST" action="'.route('interest.destroy', $row).'">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="'.csrf_token().'" />
                                    <button type="submit" class="btn btn-danger btn-xs px-2 delete-data"><i class="fas fa-trash"></i> Hapus</button>
                                </form>';
                    }
                    return '-';
                })
                ->rawColumns(['type', 'rate_percent', 'notes', 'is_active', 'action'])
                ->make(true);
        }

        $savingsCount = InterestRate::savings()->count();
        $depositsCount = InterestRate::deposits()->count();

        return view('pages.interest.index', [
            'title' => 'Manajemen Rate Bunga',
            'savingsCount' => $savingsCount,
            'depositsCount' => $depositsCount,
        ]);
    }

    public function history(Request $request)
    {
        return redirect()->route('interest.index');
    }

    public function create()
    {
        return view('pages.interest.create', ['title' => 'Tambah Rate Bunga Baru']);
    }

    public function store(StoreInterestRateRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['is_default'] = $request->has('is_default') ? $request->boolean('is_default') : false;

        DB::transaction(function () use ($data) {
            // Jika dijadikan default simpanan, nonaktifkan is_default pada simpanan lainnya
            if ($data['type'] === 'simpanan' && !empty($data['is_default'])) {
                InterestRate::where('type', 'simpanan')->where('is_default', true)->update(['is_default' => false]);
            }

            InterestRate::create($data);
        });

        return redirect()->route('interest.index')->with('success', 'Rate Bunga berhasil ditambahkan ke master data.');
    }

    public function edit(InterestRate $interest)
    {
        return view('pages.interest.edit', [
            'title' => 'Edit Rate Bunga',
            'interest' => $interest
        ]);
    }

    public function update(UpdateInterestRateRequest $request, InterestRate $interest)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        $data['is_default'] = $request->has('is_default') ? $request->boolean('is_default') : false;

        DB::transaction(function () use ($interest, $data) {
            if ($data['type'] === 'simpanan' && !empty($data['is_default'])) {
                InterestRate::where('type', 'simpanan')
                    ->where('id', '!=', $interest->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $interest->update($data);
        });

        return redirect()->route('interest.index')->with('success', 'Rate Bunga berhasil diperbarui.');
    }

    public function destroy(InterestRate $interest)
    {
        // Cek apakah rate sedang digunakan oleh fixed deposit aktif
        if ($interest->fixedDeposits()->where('status', 'active')->exists()) {
            return back()->with('error', 'Rate ini tidak dapat dihapus karena sedang digunakan oleh Deposito aktif. Anda dapat mengubah statusnya menjadi Nonaktif.');
        }

        $interest->delete();
        return back()->with('success', 'Rate Bunga berhasil dihapus.');
    }

    // API ENDPOINT untuk AJAX dropdown
    public function activeRates(Request $request)
    {
        $query = InterestRate::where('is_active', 1)->orderBy('rate_percent', 'asc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $rates = $query->get(['id', 'type', 'rate_percent', 'effective_date', 'notes', 'is_default']);

        return response()->json([
            'status' => 'success',
            'data' => $rates
        ]);
    }
}
