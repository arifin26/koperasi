<?php

namespace App\Http\Controllers;

use App\Models\FixedDeposit;
use App\Models\DepositInterestPayment;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class FixedDepositController extends Controller
{
    const PENALTY_RATE = 0.01; // 1% penalty for early liquidation

    public function __construct()
    {
        $this->middleware('role:manager')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = FixedDeposit::with('customer')->orderBy('created_at', 'desc');

            if ($request->status) {
                $data->where('status', $request->status);
            }
            if ($request->customer_id) {
                $data->where('customer_id', $request->customer_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('number', function($row) {
                    return '<a href="'.route('fixed-deposit.show', $row).'">'.$row->number.'</a>';
                })
                ->editColumn('customer', function($row) {
                    if ($row->customer) {
                        return $row->customer->name . '<small class="d-block text-muted">No. Rek: ' . $row->customer->number . '</small>';
                    }
                    return '-';
                })
                ->editColumn('amount', function($row) {
                    return 'Rp' . number_format($row->amount, 0, ',', '.');
                })
                ->editColumn('tenor_months', function($row) {
                    return $row->tenor_months . ' Bulan';
                })
                ->editColumn('rate_percent', function($row) {
                    return $row->rate_percent . '% p.a.';
                })
                ->editColumn('start_date', function($row) {
                    return $row->start_date->isoFormat('DD MMM Y');
                })
                ->editColumn('maturity_date', function($row) {
                    return $row->maturity_date->isoFormat('DD MMM Y');
                })
                ->editColumn('status', function($row) {
                    return $row->status_label;
                })
                ->addColumn('action', function($row) {
                    $btn = '<a href="'.route('fixed-deposit.show', $row).'" class="btn btn-success btn-xs px-2">Detail</a> ';
                    if ($row->status == 'active') {
                        $btn .= '<a href="'.route('fixed-deposit.extend.form', $row).'" class="btn btn-info btn-xs px-2 mx-1">Perpanjang</a> ';
                        $btn .= '<a href="'.route('fixed-deposit.liquidate.form', $row).'" class="btn btn-warning btn-xs px-2">Cairkan</a>';
                    }
                    return $btn;
                })
                ->rawColumns(['number', 'customer', 'status', 'action'])
                ->make(true);
        }

        return view('pages.fixed-deposit.index', [
            'title' => 'Manajemen Deposito'
        ]);
    }

    public function create()
    {
        $rates = \App\Models\InterestRate::whereIn('type', ['deposito', 'deposito_3_bulan'])
            ->where('is_active', 1)
            ->orderBy('effective_date', 'desc')
            ->get();

        $activeRate = $rates->first();

        return view('pages.fixed-deposit.create', [
            'title' => 'Buka Deposito Baru',
            'rates'  => $rates,
            'activeRate' => $activeRate
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|integer|min:1000000',
            'tenor_months' => 'required|integer|min:1|max:120',
            'rate_percent' => 'required|numeric|between:0,100',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        if ($customer->status !== 'active') {
            return back()->withErrors(['customer_id' => 'Nasabah tidak aktif.'])->withInput();
        }

        $startDate = Carbon::today();
        $maturityDate = $startDate->copy()->addMonths((int)$request->tenor_months);

        FixedDeposit::create([
            'number' => FixedDeposit::generateNumber(),
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'tenor_months' => $request->tenor_months,
            'rate_percent' => $request->rate_percent,
            'start_date' => $startDate,
            'maturity_date' => $maturityDate,
            'status' => 'active',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('fixed-deposit.index')->with('success', 'Deposito berhasil dibuka!');
    }

    public function show(FixedDeposit $fixed_deposit)
    {
        $fixed_deposit->load(['customer', 'interestPayments' => function($q) {
            $q->orderBy('period', 'desc');
        }]);

        return view('pages.fixed-deposit.show', [
            'title' => 'Detail Deposito ' . $fixed_deposit->number,
            'deposit' => $fixed_deposit
        ]);
    }

    // Perpanjangan
    public function extendForm(FixedDeposit $fixed_deposit)
    {
        if ($fixed_deposit->status !== 'active') {
            return back()->with('error', 'Hanya deposito aktif yang dapat diperpanjang.');
        }

        $rates = \App\Models\InterestRate::whereIn('type', ['deposito', 'deposito_3_bulan'])
            ->where('is_active', 1)
            ->orderBy('effective_date', 'desc')
            ->get();

        $activeRate = $rates->first();

        return view('pages.fixed-deposit.extend', [
            'title' => 'Perpanjang Deposito ' . $fixed_deposit->number,
            'deposit' => $fixed_deposit,
            'rates'  => $rates,
            'activeRate' => $activeRate
        ]);
    }

    public function extend(Request $request, FixedDeposit $fixed_deposit)
    {
        $request->validate([
            'tenor_months' => 'required|integer|min:1|max:120',
            'rate_percent' => 'required|numeric|between:0,100',
        ]);

        if ($fixed_deposit->status !== 'active') {
            return back()->with('error', 'Hanya deposito aktif yang dapat diperpanjang.');
        }

        DB::transaction(function () use ($fixed_deposit, $request) {
            // Mark old deposit as extended
            $fixed_deposit->update([
                'status' => 'extended',
                'updated_by' => auth()->id(),
            ]);

            // Create new deposit
            $startDate = Carbon::today();
            FixedDeposit::create([
                'number' => FixedDeposit::generateNumber(),
                'customer_id' => $fixed_deposit->customer_id,
                'amount' => $fixed_deposit->amount,
                'tenor_months' => $request->tenor_months,
                'rate_percent' => $request->rate_percent,
                'start_date' => $startDate,
                'maturity_date' => $startDate->copy()->addMonths((int)$request->tenor_months),
                'status' => 'active',
                'extended_from_id' => $fixed_deposit->id,
                'notes' => 'Perpanjangan dari ' . $fixed_deposit->number,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('fixed-deposit.index')->with('success', 'Deposito berhasil diperpanjang!');
    }

    // Pencairan
    public function liquidateForm(FixedDeposit $fixed_deposit)
    {
        if ($fixed_deposit->status !== 'active') {
            return back()->with('error', 'Hanya deposito aktif yang dapat dicairkan.');
        }

        $isEarly = Carbon::today()->lt($fixed_deposit->maturity_date);
        $penalty = $isEarly ? (int) floor($fixed_deposit->amount * self::PENALTY_RATE) : 0;
        $netAmount = $fixed_deposit->amount - $penalty;

        return view('pages.fixed-deposit.liquidate', [
            'title' => 'Cairkan Deposito ' . $fixed_deposit->number,
            'deposit' => $fixed_deposit,
            'isEarly' => $isEarly,
            'penalty' => $penalty,
            'netAmount' => $netAmount,
        ]);
    }

    public function liquidate(Request $request, FixedDeposit $fixed_deposit)
    {
        if ($fixed_deposit->status !== 'active') {
            return back()->with('error', 'Hanya deposito aktif yang dapat dicairkan.');
        }

        $isEarly = Carbon::today()->lt($fixed_deposit->maturity_date);
        $penalty = $isEarly ? (int) floor($fixed_deposit->amount * self::PENALTY_RATE) : 0;
        $netAmount = $fixed_deposit->amount - $penalty;

        DB::transaction(function () use ($fixed_deposit, $netAmount, $penalty, $isEarly) {
            // Mark deposit as liquidated
            $fixed_deposit->update([
                'status' => 'liquidated',
                'liquidated_at' => now(),
                'notes' => ($fixed_deposit->notes ? $fixed_deposit->notes . "\n" : '') .
                    ($isEarly ? "Pencairan dini. Penalti: Rp " . number_format($penalty) : "Pencairan jatuh tempo."),
                'updated_by' => auth()->id(),
            ]);

            // Transfer net amount to savings (sukarela)
            $txn = Deposit::create([
                'customer_id' => $fixed_deposit->customer_id,
                'type' => 'sukarela',
                'amount' => $netAmount,
                'previous_balance' => 0,
                'current_balance' => 0,
                'notes' => 'Pencairan Deposito ' . $fixed_deposit->number .
                    ($isEarly ? ' (Penalti Rp ' . number_format($penalty) . ')' : ''),
                'created_by' => auth()->id(),
            ]);

            Deposit::recalculateBalance($fixed_deposit->customer_id);
        });

        return redirect()->route('fixed-deposit.index')->with('success', 'Deposito berhasil dicairkan! Dana telah ditransfer ke Simpanan Sukarela nasabah.');
    }

    public function print(Request $request)
    {
        $query = FixedDeposit::with('customer');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('created_at', 'desc')->get();
        $manager = User::where('role', 'manager')->first();

        $pdf = PDF::loadView('pages.fixed-deposit.print', [
            'title' => 'Laporan Deposito',
            'user' => auth()->user(),
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data,
        ]);
        $pdf->setPaper('A4', 'landscape');

        $filename = Carbon::now()->isoFormat('DD-MM-Y') . '_laporan_deposito_' . time() . '.pdf';
        return $pdf->download($filename);
    }
}
