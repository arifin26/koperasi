<?php

namespace App\Http\Controllers;

use App\Models\FixedDeposit;
use App\Models\DepositInterestPayment;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\InterestRate;
use App\Models\User;
use App\Helpers\TerbilangHelper;
use App\Http\Requests\StoreFixedDepositRequest;
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
        $this->middleware('role:manager')->only(['destroy', 'updateBunga']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = FixedDeposit::with(['customer', 'validator'])->orderBy('created_at', 'desc');

            if ($request->status) {
                $data->where('status', $request->status);
            }
            if ($request->customer_id) {
                $data->where('customer_id', $request->customer_id);
            }
            if ($request->account_number) {
                $data->where('account_number', 'LIKE', '%' . $request->account_number . '%');
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('number', function($row) {
                    return '<a href="'.route('fixed-deposit.show', $row).'">'.$row->number.'</a>';
                })
                ->addColumn('account_number', function($row) {
                    return $row->account_number ?? '-';
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
                    $validateBtn = '';
                    if ($row->is_validated) {
                        $validateBtn = '<span class="badge badge-success px-2 py-1 mr-1" title="Divalidasi oleh ' . ($row->validator->name ?? 'User') . ' pada ' . ($row->validated_at ? $row->validated_at->isoFormat('DD/MM/Y HH:mm') : '') . '"><i class="fas fa-check-circle"></i> Valid</span>
                                        <a href="' . route('fixed-deposit.validation-print', $row) . '" target="_blank" class="btn btn-outline-dark btn-xs px-2 mr-1" title="Cetak Slip Validasi"><i class="fas fa-barcode"></i> Cetak Validasi</a>';
                    } else {
                        $validateBtn = '<button type="button" class="btn btn-warning btn-xs px-2 mr-1 btn-validate" data-url="' . route('fixed-deposit.validate', $row) . '" data-title="Deposito ' . $row->number . ' - ' . ($row->customer->name ?? '') . '"><i class="fas fa-check"></i> Validasi</button>';
                    }

                    $btn = '<a href="'.route('fixed-deposit.receipt', $row).'" target="_blank" class="btn btn-secondary btn-xs px-2"><i class="fas fa-print"></i> Kwitansi</a> ';
                    $btn .= $validateBtn . ' ';
                    $btn .= '<a href="'.route('fixed-deposit.show', $row).'" class="btn btn-success btn-xs px-2 mx-1">Detail</a> ';
                    if ($row->status == 'active') {
                        $btn .= '<a href="'.route('fixed-deposit.extend.form', $row).'" class="btn btn-info btn-xs px-2 mr-1">Perpanjang</a> ';
                        $btn .= '<a href="'.route('fixed-deposit.liquidate.form', $row).'" class="btn btn-warning btn-xs px-2">Cairkan</a>';
                    }
                    return $btn;
                })
                ->rawColumns(['number', 'customer', 'status', 'action'])
                ->make(true);
        }

        $currentPeriod = now()->format('Y-m');
        $today = now()->format('Y-m-d');
        $todayDay = (int) now()->format('d');

        // 1. Total Bunga Deposito Terdistribusi (Bulan Ini)
        $bungaDepositoTerdistribusi = DepositInterestPayment::where('period', $currentPeriod)
            ->sum('interest_amount');
        $bungaDepositoTerdistribusiCount = DepositInterestPayment::where('period', $currentPeriod)
            ->count();
        $bungaDepositoTerdistribusiNasabahCount = DepositInterestPayment::where('period', $currentPeriod)
            ->join('fixed_deposits', 'deposit_interest_payments.fixed_deposit_id', '=', 'fixed_deposits.id')
            ->distinct('fixed_deposits.customer_id')
            ->count('fixed_deposits.customer_id');

        // 2. Bunga Deposito Jatuh Tempo Hari Ini / Menunggu Update
        // Yaitu deposito aktif yang start_date milestonya <= hari ini, belum dibayar pada periode berjalan
        $paidDepositIdsThisMonth = DepositInterestPayment::where('period', $currentPeriod)
            ->pluck('fixed_deposit_id');

        $pendingDeposits = FixedDeposit::whereIn('status', ['active', 'matured'])
            ->whereNotIn('id', $paidDepositIdsThisMonth)
            ->get()
            ->filter(function($item) use ($todayDay, $currentPeriod) {
                $startDay = (int) $item->start_date->format('d');
                $isMatured = now()->gte($item->maturity_date);
                // Jatuh tempo bunga jika milestone day <= hari ini atau sudah jatuh tempo keseluruhan
                return $startDay <= $todayDay || $isMatured;
            });

        $bungaDepositoMenunggu = $pendingDeposits->sum(function($item) {
            return (int) floor($item->amount * ($item->rate_percent / 100) / 12);
        });
        $bungaDepositoMenungguCount = $pendingDeposits->count();
        $bungaDepositoMenungguNasabahCount = $pendingDeposits->pluck('customer_id')->unique()->count();

        return view('pages.fixed-deposit.index', [
            'title' => 'Manajemen Deposito',
            'bungaDepositoTerdistribusi' => $bungaDepositoTerdistribusi,
            'bungaDepositoTerdistribusiCount' => $bungaDepositoTerdistribusiCount,
            'bungaDepositoTerdistribusiNasabahCount' => $bungaDepositoTerdistribusiNasabahCount,
            'bungaDepositoMenunggu' => $bungaDepositoMenunggu,
            'bungaDepositoMenungguCount' => $bungaDepositoMenungguCount,
            'bungaDepositoMenungguNasabahCount' => $bungaDepositoMenungguNasabahCount,
        ]);
    }

    public function create(Request $request)
    {
        $rates = \App\Models\InterestRate::where('type', 'deposito')
            ->where('is_active', 1)
            ->orderBy('effective_date', 'desc')
            ->get();

        $activeRate = $rates->first();
        $preselectedCustomerId = $request->query('customer_id');
        $preselectedCustomer = null;

        if ($preselectedCustomerId) {
            $preselectedCustomer = Customer::find($preselectedCustomerId);
        }

        return view('pages.fixed-deposit.create', [
            'title' => 'Buka Deposito',
            'rates'  => $rates,
            'activeRate' => $activeRate,
            'preselectedCustomer' => $preselectedCustomer,
            'preselectedCustomerId' => $preselectedCustomerId,
        ]);
    }

    public function store(StoreFixedDepositRequest $request)
    {
        $customer = Customer::findOrFail($request->customer_id);
        if ($customer->status !== 'active') {
            return back()->withErrors(['customer_id' => 'Nasabah tidak aktif.'])->withInput();
        }

        // Validasi: Nasabah wajib memiliki rekening/transaksi simpanan aktif terlebih dahulu
        $hasDeposit = Deposit::where('customer_id', $customer->id)->exists();
        if (!$hasDeposit) {
            return back()->withErrors([
                'customer_id' => 'Nasabah belum memiliki rekening/transaksi simpanan. Silakan buat transaksi simpanan terlebih dahulu sebelum membuka deposito.'
            ])->with('require_deposit', true)->with('customer_id', $customer->id)->withInput();
        }

        $startDate = Carbon::today();
        $maturityDate = $startDate->copy()->addMonths((int)$request->tenor_months);

        $deposit = FixedDeposit::create([
            'number' => FixedDeposit::generateNumber(),
            'account_number' => $request->account_number,
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

        return redirect()->route('fixed-deposit.index')
            ->with('success', 'Deposito berhasil dibuka!')
            ->with('receipt_url', route('fixed-deposit.receipt', $deposit));
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

        $rates = \App\Models\InterestRate::where('type', 'deposito')
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

        $txn = null;
        DB::transaction(function () use ($fixed_deposit, $netAmount, $penalty, $isEarly, &$txn) {
            // Mark deposit as liquidated
            $fixed_deposit->update([
                'status' => 'liquidated',
                'liquidated_at' => now(),
                'notes' => ($fixed_deposit->notes ? $fixed_deposit->notes . "\n" : '') .
                    ($isEarly ? "Pencairan dini. Penalti: Rp " . number_format($penalty) : "Pencairan jatuh tempo."),
                'updated_by' => auth()->id(),
            ]);

            // Transfer net amount to savings (simpanan)
            $txn = Deposit::create([
                'customer_id' => $fixed_deposit->customer_id,
                'type' => 'simpanan',
                'amount' => $netAmount,
                'previous_balance' => 0,
                'current_balance' => 0,
                'notes' => 'Pencairan Deposito ' . $fixed_deposit->number .
                    ($isEarly ? ' (Penalti Rp ' . number_format($penalty) . ')' : ''),
                'created_by' => auth()->id(),
            ]);

            Deposit::recalculateBalance($fixed_deposit->customer_id);
        });

        $redirect = redirect()->route('fixed-deposit.index')
            ->with('success', 'Deposito berhasil dicairkan! Dana telah ditransfer ke Simpanan nasabah.');

        if ($txn) {
            $redirect->with('receipt_url', route('transaction.deposit.receipt', $txn))
                     ->with('passbook_url', route('transaction.deposit.passbook', $txn));
        }

        return $redirect;
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
        $pdf->setPaper('A4', 'portrait');

        $filename = Carbon::now()->isoFormat('DD-MM-Y') . '_laporan_deposito_' . time() . '.pdf';
        return $pdf->download($filename);
    }

    public function receipt(FixedDeposit $fixed_deposit)
    {
        $fixed_deposit->load(['customer', 'creator', 'validator']);
        $terbilang = TerbilangHelper::make($fixed_deposit->amount);

        $pdf = Pdf::loadView('pages.fixed-deposit.receipt', [
            'title' => 'Tanda Terima Deposito ' . $fixed_deposit->number,
            'fixed_deposit' => $fixed_deposit,
            'terbilang' => $terbilang,
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Kwitansi_Deposito_' . $fixed_deposit->number . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function validateTransaction(FixedDeposit $fixed_deposit)
    {
        if ($fixed_deposit->validated_at) {
            return response()->json([
                'success' => false,
                'message' => 'Deposito ini sudah divalidasi sebelumnya oleh ' . ($fixed_deposit->validator->name ?? 'User') . '.',
            ], 422);
        }

        $fixed_deposit->update([
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deposito ' . $fixed_deposit->number . ' berhasil divalidasi.',
            'validation_print_url' => route('fixed-deposit.validation-print', $fixed_deposit),
        ]);
    }

    public function printValidation(FixedDeposit $fixed_deposit)
    {
        $fixed_deposit->load(['customer', 'validator']);
        $validatorName = $fixed_deposit->validator->name ?? auth()->user()->name ?? 'TELLER';
        $validatedAt = $fixed_deposit->validated_at ? $fixed_deposit->validated_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s');

        return view('pages.transaction.validation.print', [
            'title' => 'Cetak Validasi Deposito - ' . $fixed_deposit->number,
            'typeLabel' => 'PEMBUKAAN DEPOSITO',
            'validatorName' => $validatorName,
            'accountNumber' => $fixed_deposit->customer->number ?? '-',
            'validatedAt' => $validatedAt,
            'amount' => $fixed_deposit->amount,
        ]);
    }

    public function destroy(FixedDeposit $fixed_deposit)
    {
        try {
            DB::beginTransaction();
            $deletedId = $fixed_deposit->id;
            $fixed_deposit->delete();
            DB::commit();

            return back()
                ->with('success', 'Berhasil menghapus data deposito nasabah!')
                ->with('deletion_receipt_url', route('fixed-deposit.destroy-receipt', $deletedId));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroyReceipt($id)
    {
        $fixed_deposit = FixedDeposit::withTrashed()->with(['customer', 'creator'])->findOrFail($id);
        abort_if(!$fixed_deposit->trashed(), 403, 'Deposito ini belum dihapus.');

        $terbilang = TerbilangHelper::make($fixed_deposit->amount);

        $pdf = Pdf::loadView('pages.fixed-deposit.destroy-receipt', [
            'title'         => 'Bukti Penghapusan Deposito ' . $fixed_deposit->number,
            'fixed_deposit' => $fixed_deposit,
            'terbilang'     => $terbilang,
            'deletedBy'     => auth()->user()->name ?? '-',
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Bukti_Hapus_Deposito_' . $fixed_deposit->number . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function updateBunga(Request $request, \App\Services\DepositInterestService $service)
    {
        $request->validate([
            'process_date' => 'nullable|date',
        ]);

        try {
            $result = $service->processDepositInterest(
                $request->process_date,
                auth()->id()
            );

            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses update bunga deposito: ' . $th->getMessage(),
            ], 500);
        }
    }
}
