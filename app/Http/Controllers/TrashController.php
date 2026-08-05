<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\FixedDeposit;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class TrashController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager');
    }

    public function index(Request $request)
    {
        $module = $request->module ?? 'customers';

        if ($request->ajax()) {
            switch ($module) {
                case 'customers':
                    return $this->trashedCustomers();
                case 'deposits':
                    return $this->trashedDeposits();
                case 'fixed_deposits':
                    return $this->trashedFixedDeposits();
                case 'users':
                    return $this->trashedUsers();
            }
        }

        return view('pages.utility.trash', [
            'title' => 'Data Terhapus (Arsip)',
            'module' => $module,
        ]);
    }

    public function restore(Request $request, $module, $id)
    {
        switch ($module) {
            case 'customers':
                Customer::withTrashed()->findOrFail($id)->restore();
                break;
            case 'deposits':
                $deposit = Deposit::withTrashed()->findOrFail($id);
                $deposit->restore();
                Deposit::recalculateBalance($deposit->customer_id);
                break;
            case 'fixed_deposits':
                FixedDeposit::withTrashed()->findOrFail($id)->restore();
                break;
            case 'users':
                User::withTrashed()->findOrFail($id)->restore();
                break;
            default:
                return back()->with('error', 'Modul tidak valid.');
        }

        return back()->with('success', 'Data berhasil dikembalikan!');
    }

    public function forceDelete(Request $request, $module, $id)
    {
        switch ($module) {
            case 'customers':
                Customer::withTrashed()->findOrFail($id)->forceDelete();
                break;
            case 'deposits':
                Deposit::withTrashed()->findOrFail($id)->forceDelete();
                break;
            case 'fixed_deposits':
                FixedDeposit::withTrashed()->findOrFail($id)->forceDelete();
                break;
            case 'users':
                User::withTrashed()->findOrFail($id)->forceDelete();
                break;
            default:
                return back()->with('error', 'Modul tidak valid.');
        }

        return back()->with('success', 'Data berhasil dihapus permanen!');
    }

    // --- Private DataTable methods ---

    private function trashedCustomers()
    {
        $data = Customer::onlyTrashed()->orderBy('deleted_at', 'desc');
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('deleted_at', fn($r) => Carbon::parse($r->deleted_at)->isoFormat('DD MMM Y HH:mm'))
            ->addColumn('action', fn($r) => $this->actionButtons('customers', $r->id))
            ->rawColumns(['action'])
            ->make(true);
    }

    private function trashedDeposits()
    {
        $data = Deposit::onlyTrashed()->with('customer')->orderBy('deleted_at', 'desc');
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->editColumn('amount', fn($r) => 'Rp' . number_format($r->amount, 0, ',', '.'))
            ->editColumn('type', fn($r) => ucfirst($r->type))
            ->editColumn('deleted_at', fn($r) => Carbon::parse($r->deleted_at)->isoFormat('DD MMM Y HH:mm'))
            ->addColumn('action', fn($r) => $this->actionButtons('deposits', $r->id))
            ->rawColumns(['action'])
            ->make(true);
    }

    private function trashedFixedDeposits()
    {
        $data = FixedDeposit::onlyTrashed()->with('customer')->orderBy('deleted_at', 'desc');
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->editColumn('amount', fn($r) => 'Rp' . number_format($r->amount, 0, ',', '.'))
            ->editColumn('deleted_at', fn($r) => Carbon::parse($r->deleted_at)->isoFormat('DD MMM Y HH:mm'))
            ->addColumn('action', fn($r) => $this->actionButtons('fixed_deposits', $r->id))
            ->rawColumns(['action'])
            ->make(true);
    }

    private function trashedUsers()
    {
        $data = User::onlyTrashed()->orderBy('deleted_at', 'desc');
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('deleted_at', fn($r) => Carbon::parse($r->deleted_at)->isoFormat('DD MMM Y HH:mm'))
            ->addColumn('action', fn($r) => $this->actionButtons('users', $r->id))
            ->rawColumns(['action'])
            ->make(true);
    }

    private function actionButtons($module, $id)
    {
        return '<form class="d-inline" method="POST" action="' . route('trash.restore', [$module, $id]) . '">
                    ' . csrf_field() . '
                    <button type="submit" class="btn btn-success btn-xs px-2" onclick="return confirm(\'Kembalikan data ini?\')">
                        <i class="fas fa-undo"></i> Kembalikan
                    </button>
                </form>
                <form class="d-inline" method="POST" action="' . route('trash.force-delete', [$module, $id]) . '">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="submit" class="btn btn-danger btn-xs px-2 ml-1" onclick="return confirm(\'HAPUS PERMANEN? Data tidak dapat dikembalikan!\')">
                        <i class="fas fa-trash"></i> Hapus Permanen
                    </button>
                </form>';
    }
}
