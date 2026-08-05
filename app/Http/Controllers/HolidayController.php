<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager')->except(['index']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Holiday::query();
            
            if ($request->year) {
                $data->where('year', $request->year);
            }

            $data->orderBy('date', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('date', function($row) {
                    return Carbon::parse($row->date)->isoFormat('DD MMMM Y');
                })
                ->editColumn('type', function($row) {
                    if ($row->type == 'holiday') {
                        return '<span class="badge badge-danger">Libur</span>';
                    }
                    return '<span class="badge badge-success">Kerja Pengganti</span>';
                })
                ->addColumn('action', function($row) {
                    if (auth()->user()->role != 'manager') return '';

                    return '<a href="'.route('holiday.edit', $row).'" class="btn btn-primary btn-xs px-2 mx-1">Edit</a>
                            <form class="d-inline" method="POST" action="'.route('holiday.destroy', $row).'">
                                '.method_field('DELETE').'
                                '.csrf_field().'
                                <button type="button" class="btn btn-danger btn-xs px-2 btn-delete">Hapus</button>
                            </form>';
                })
                ->rawColumns(['type', 'action'])
                ->make(true);
        }

        return view('pages.holiday.index', ['title' => 'Manajemen Hari Libur']);
    }

    public function create()
    {
        return view('pages.holiday.create', ['title' => 'Tambah Hari Libur']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:holidays,date,NULL,id,deleted_at,NULL',
            'name' => 'required|string|max:100',
            'type' => 'required|in:holiday,workday',
        ]);

        $year = Carbon::parse($request->date)->year;

        Holiday::create([
            'date' => $request->date,
            'name' => $request->name,
            'type' => $request->type,
            'year' => $year,
            'description' => $request->description,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('holiday.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Holiday $holiday)
    {
        return view('pages.holiday.edit', [
            'title' => 'Edit Hari Libur',
            'holiday' => $holiday
        ]);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'date' => 'required|date|unique:holidays,date,'.$holiday->id.',id,deleted_at,NULL',
            'name' => 'required|string|max:100',
            'type' => 'required|in:holiday,workday',
        ]);

        $year = Carbon::parse($request->date)->year;

        $holiday->update([
            'date' => $request->date,
            'name' => $request->name,
            'type' => $request->type,
            'year' => $year,
            'description' => $request->description,
            'updated_by' => auth()->id()
        ]);

        return redirect()->route('holiday.index')->with('success', 'Hari libur berhasil diubah.');
    }

    public function destroy(Holiday $holiday)
    {
        // Pengecekan keamanan: Jika tanggal ini sudah pernah masuk ke daily_interest_accumulations
        // maka idealnya tidak boleh dihapus. Untuk sekarang kita abaikan karena soft deletes.
        $holiday->delete();
        return redirect()->route('holiday.index')->with('success', 'Hari libur berhasil dihapus.');
    }

    // API ENDPOINT
    public function check(Request $request)
    {
        $dateStr = $request->tanggal ?? date('Y-m-d');
        $date = Carbon::parse($dateStr);
        $dayName = $date->englishDayOfWeek;

        // Cek priority 1 & 2: Ada di tabel holiday?
        $holiday = Holiday::where('date', $dateStr)->first();

        if ($holiday) {
            if ($holiday->type == 'workday') {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'date' => $dateStr,
                        'day_name' => $dayName,
                        'is_workday' => true,
                        'reason' => 'Hari Kerja Pengganti: ' . $holiday->name
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'date' => $dateStr,
                        'day_name' => $dayName,
                        'is_workday' => false,
                        'reason' => 'Hari Libur: ' . $holiday->name
                    ]
                ]);
            }
        }

        // Cek priority 3 & 4: Weekend atau Weekday biasa?
        if ($date->isWeekend()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'date' => $dateStr,
                    'day_name' => $dayName,
                    'is_workday' => false,
                    'reason' => 'Akhir Pekan (Sabtu/Minggu)'
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $dateStr,
                'day_name' => $dayName,
                'is_workday' => true,
                'reason' => 'Hari Kerja Normal'
            ]
        ]);
    }
}
