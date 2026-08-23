<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepositRequest;
use App\Http\Requests\UpdateDepositRequest;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Helpers\TerbilangHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\DataTables;

class WithdrawalController extends Controller
{

    public function __construct()
    {
        $this->title = 'Transaksi - Penarikan';
        $this->code = 'PE';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Deposit::with(['customer', 'validator'])->where('type', 'penarikan')->orderBy('created_at');

            if ($request->customer) {
                $data = $data->where('customer_id', $request->customer);
            }

            if ($request->from) {
                $data = $data->whereDate('created_at', '>=', $request->from);
            }

            if ($request->to) {
                $data = $data->whereDate('created_at', '<=', $request->to);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $validateBtn = '';
                    if ($row->is_validated) {
                        $validateBtn = '<span class="badge badge-success px-2 py-1 mr-1" title="Divalidasi oleh ' . ($row->validator->name ?? 'User') . ' pada ' . ($row->validated_at ? $row->validated_at->isoFormat('DD/MM/Y HH:mm') : '') . '"><i class="fas fa-check-circle"></i> Valid</span>
                                        <a href="' . route('transaction.withdrawal.validation-print', $row) . '" target="_blank" class="btn btn-outline-dark btn-xs px-2 mr-1" title="Cetak Slip Validasi"><i class="fas fa-barcode"></i> Cetak Validasi</a>';
                    } else {
                        $validateBtn = '<button type="button" class="btn btn-warning btn-xs px-2 mr-1 btn-validate" data-url="' . route('transaction.withdrawal.validate', $row) . '" data-title="Penarikan ' . $this->buildTransactionCode($row->id) . ' - ' . ($row->customer->name ?? '') . '"><i class="fas fa-check"></i> Validasi</button>';
                    }

                    if ($row->customer) {
                        return '<a href="' . route('transaction.withdrawal.receipt', $row) . '" target="_blank" class="btn btn-secondary btn-xs px-2"><i class="fas fa-print"></i> Kwitansi</a>
                                <button type="button" class="btn btn-info btn-xs px-2 print-passbook-btn" data-url="' . route('transaction.withdrawal.passbook', $row) . '" data-title="Penarikan ' . $this->buildTransactionCode($row->id) . ' - ' . ($row->customer->name ?? '') . '"><i class="fas fa-book"></i> Buku</button>
                                ' . $validateBtn . '
                                <a href="' . route('transaction.withdrawal.show', $row) . '" class="btn btn-success btn-xs px-2 mx-1"> Detail </a>
                                <a href="' . route('transaction.withdrawal.edit', $row) . '" class="btn btn-primary btn-xs px-2 mr-1"> Edit </a>
                                <form class="d-inline" method="POST" action="' . route('transaction.withdrawal.destroy', $row) . '">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="' . csrf_token() . '" />
                                    <button type="submit" class="btn btn-danger btn-xs px-2 delete-data"> Hapus </button>
                                </form>';
                    }

                    return '<a href="' . route('transaction.withdrawal.receipt', $row) . '" target="_blank" class="btn btn-secondary btn-xs px-2 mr-1"><i class="fas fa-print"></i> Kwitansi</a>
                        <button type="button" class="btn btn-info btn-xs px-2 mr-1 print-passbook-btn" data-url="' . route('transaction.withdrawal.passbook', $row) . '" data-title="Penarikan ' . $this->buildTransactionCode($row->id) . '"><i class="fas fa-book"></i> Buku</button>
                        ' . $validateBtn . '
                        <form class="d-inline" method="POST" action="' . route('transaction.withdrawal.destroy', $row) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="' . csrf_token() . '" />
                        <button type="submit" class="btn btn-danger btn-xs px-2 delete-data"> Hapus </button>
                    </form>';
                })
                ->editColumn('id', function($row) {
                    return $this->buildTransactionCode($row->id);
                })
                ->editColumn('created_at', function($row) {
                    return Carbon::parse($row->created_at)->isoFormat('DD-MM-Y');
                })
                ->editColumn('customer', function($row) {
                    if ($row->customer) {
                        return $row->customer->name . '<small class="small d-block">No. Rek: ' . $row->customer->number . '</small>';
                    }

                    return 'Nasabah Tidak Ditemukan';
                })
                ->editColumn('amount', function($row) {
                    return 'Rp' . number_format($row->amount, 2, ',', '.');
                })
                ->editColumn('masuk', function($row) {
                    return 'Rp0';
                })
                ->editColumn('keluar', function($row) {
                    return 'Rp' . number_format($row->amount, 0, ',', '.');
                })
                ->editColumn('current_balance', function($row) {
                    return 'Rp' . number_format($row->current_balance, 2, ',', '.');
                })
                ->rawColumns(['action', 'customer'])
                ->make(true);
        }
        return view('pages.transaction.withdrawal.index', [
            'title' => $this->title,
            'customers' => Customer::where('status', 'active')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.transaction.withdrawal.create', [
            'title' => $this->buildTitle('baru'),
            'customers' => Customer::where('status', 'active')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreDepositRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDepositRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Lock the customer to prevent race conditions during concurrent requests
            Customer::where('id', $request->customer_id)->lockForUpdate()->first();
            
            // Get current accurate balance
            $balances = Deposit::recalculateBalance($request->customer_id);
            $saldo = $balances['simpanan'] ?? 0;
            
            if ($request->amount > $saldo) {
                DB::rollBack();
                return back()->with('error', 'Saldo tidak mencukupi. Saldo tersedia: Rp ' . number_format($saldo, 0, ',', '.'));
            }

            $data = $request->all();
            if ($request->filled('created_at')) {
                $data['created_at'] = Carbon::parse($request->created_at)->setTimeFrom(now());
            }
            $data['previous_balance'] = 0;
            $data['current_balance'] = 0;
            $data['created_by'] = auth()->id();
            
            $deposit = Deposit::create($data);
            Deposit::recalculateBalance($request->customer_id);
            
            DB::commit();
            return redirect()->route('transaction.withdrawal.index')
                ->with('success', 'Berhasil menarik simpanan nasabah!')
                ->with('receipt_url', route('transaction.withdrawal.receipt', $deposit))
                ->with('passbook_url', route('transaction.withdrawal.passbook', $deposit));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Deposit  $penarikan
     * @return \Illuminate\Http\Response
     */
    public function show(Deposit $penarikan)
    {
        return view('pages.transaction.withdrawal.show', [
            'title' => $this->buildTitle('detail'),
            'deposit' => $penarikan,
            'code' => $this->buildTransactionCode($penarikan->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Deposit  $penarikan
     * @return \Illuminate\Http\Response
     */
    public function edit(Deposit $penarikan)
    {
        return view('pages.transaction.withdrawal.edit', [
            'title' => $this->buildTitle('edit'),
            'code' => $this->buildTransactionCode($penarikan->id),
            'deposit' => $penarikan,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDepositRequest  $request
     * @param  \App\Models\Deposit  $penarikan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDepositRequest $request, Deposit $penarikan)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['updated_by'] = auth()->id();
            $penarikan->update($data);
            
            // Check if after update, the balance becomes negative
            $balances = Deposit::recalculateBalance($penarikan->customer_id);
            if ($balances['simpanan'] < 0) {
                DB::rollBack();
                return back()->with('error', 'Update dibatalkan karena menyebabkan saldo akhir menjadi negatif.');
            }
            
            DB::commit();
            return back()->with('success', 'Berhasil mengedit penarikan simpanan nasabah!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Deposit  $penarikan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Deposit $penarikan)
    {
        try {
            DB::beginTransaction();
            $deletedId = $penarikan->id;
            $customerId = $penarikan->customer_id;
            $penarikan->delete();
            
            Deposit::recalculateBalance($customerId);
            DB::commit();
            
            return back()
                ->with('success', 'Berhasil menghapus penarikan simpanan nasabah!')
                ->with('deletion_receipt_url', route('transaction.withdrawal.destroy-receipt', $deletedId));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function print(Request $request)
    {
        $filter = $request->validate([
            'time_from' => 'required',
            'time_to' => 'required',
        ]);

        $time_from = date('d-m-Y', strtotime($filter['time_from']));
        $time_to = date('d-m-Y', strtotime($filter['time_to']));

        $data = Deposit::with('customer')->where('type', 'penarikan')->whereBetween('created_at', $filter)->get();
        $manager = User::where('role', 'manager')->first();
        $filename = Carbon::now()->isoFormat('DD-MM-Y') . '_-_laporan_penarikan_nasabah_periode_' . $time_from . '_-_' . $time_to  . '_' . time() . '.pdf';
        $pdf = PDF::loadView('pages.transaction.withdrawal.print', [
            'title' => 'Laporan Penarikan Nasabah',
            'user' => auth()->user(),
            'filter' => "$time_from sampai $time_to",
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data
        ]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }

    public function receipt(Deposit $penarikan)
    {
        $penarikan->load(['customer', 'creator', 'validator']);
        $code = $this->buildTransactionCode($penarikan->id);
        $terbilang = TerbilangHelper::make($penarikan->amount);

        $pdf = Pdf::loadView('pages.transaction.withdrawal.receipt', [
            'title' => 'Kwitansi Penarikan ' . $code,
            'deposit' => $penarikan,
            'code' => $code,
            'terbilang' => $terbilang,
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Kwitansi_Penarikan_' . $code . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function validateTransaction(Deposit $penarikan)
    {
        if ($penarikan->validated_at) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah divalidasi sebelumnya oleh ' . ($penarikan->validator->name ?? 'User') . '.',
            ], 422);
        }

        $penarikan->update([
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi penarikan ' . $this->buildTransactionCode($penarikan->id) . ' berhasil divalidasi.',
            'validation_print_url' => route('transaction.withdrawal.validation-print', $penarikan),
        ]);
    }

    public function printValidation(Deposit $penarikan)
    {
        $penarikan->load(['customer', 'validator']);
        $validatorName = $penarikan->validator->name ?? auth()->user()->name ?? 'TELLER';
        $validatedAt = $penarikan->validated_at ? $penarikan->validated_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s');

        return view('pages.transaction.validation.print', [
            'title' => 'Cetak Validasi Penarikan - ' . $this->buildTransactionCode($penarikan->id),
            'typeLabel' => 'PENARIKAN TUNAI',
            'validatorName' => $validatorName,
            'accountNumber' => $penarikan->customer->number ?? '-',
            'validatedAt' => $validatedAt,
            'amount' => $penarikan->amount,
        ]);
    }

    public function destroyReceipt($id)
    {
        $penarikan = Deposit::withTrashed()->with(['customer', 'creator'])->findOrFail($id);
        abort_if(!$penarikan->trashed(), 403, 'Transaksi ini belum dihapus.');

        $code = $this->buildTransactionCode($penarikan->id);
        $terbilang = TerbilangHelper::make($penarikan->amount);

        $pdf = Pdf::loadView('pages.transaction.withdrawal.destroy-receipt', [
            'title'     => 'Bukti Penghapusan Penarikan ' . $code,
            'deposit'   => $penarikan,
            'code'      => $code,
            'terbilang' => $terbilang,
            'deletedBy' => auth()->user()->name ?? '-',
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Bukti_Hapus_Penarikan_' . $code . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function passbook(Request $request, Deposit $penarikan)
    {
        $penarikan->load(['customer', 'creator']);
        $startRow = (int) ($request->query('row', 1));
        if ($startRow < 1 || $startRow > 30) {
            $startRow = 1;
        }

        return view('pages.transaction.passbook.print', [
            'title' => 'Cetak Buku Tabungan - ' . ($penarikan->customer->name ?? 'Nasabah'),
            'transactions' => [$penarikan],
            'startRow' => $startRow,
            'customer' => $penarikan->customer,
        ]);
    }
}
