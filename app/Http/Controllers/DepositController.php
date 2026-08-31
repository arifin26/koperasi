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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class DepositController extends Controller
{


    public function __construct()
    {
        $this->title = 'Transaksi - Simpanan';
        $this->code = 'SI';
        $this->middleware('role:manager')->only(['updateBunga']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Deposit::with(['customer', 'validator'])->whereNot('type', 'penarikan')->orderBy('created_at');

            if ($request->customer) {
                $data = $data->where('customer_id', $request->customer);
            }

            if ($request->type) {
                $data = $data->where('type', $request->type);
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
                                        <a href="' . route('transaction.deposit.validation-print', $row) . '" target="_blank" class="btn btn-outline-dark btn-xs px-2 mr-1" title="Cetak Slip Validasi"><i class="fas fa-barcode"></i> Cetak Validasi</a>';
                    } else {
                        $validateBtn = '<button type="button" class="btn btn-warning btn-xs px-2 mr-1 btn-validate" data-url="' . route('transaction.deposit.validate', $row) . '" data-title="Simpanan ' . $this->buildTransactionCode($row->id) . ' - ' . ($row->customer->name ?? '') . '"><i class="fas fa-check"></i> Validasi</button>';
                    }

                    if ($row->customer) {
                        return '<a href="' . route('transaction.deposit.receipt', $row) . '" target="_blank" class="btn btn-secondary btn-xs px-2"><i class="fas fa-print"></i> Kwitansi</a>
                                <button type="button" class="btn btn-info btn-xs px-2 print-passbook-btn" data-url="' . route('transaction.deposit.passbook', $row) . '" data-title="Simpanan ' . $this->buildTransactionCode($row->id) . ' - ' . ($row->customer->name ?? '') . '"><i class="fas fa-book"></i> Buku</button>
                                ' . $validateBtn . '
                                <a href="' . route('transaction.deposit.show', $row) . '" class="btn btn-success btn-xs px-2 mx-1"> Detail </a>
                                <a href="' . route('transaction.deposit.edit', $row) . '" class="btn btn-primary btn-xs px-2 mr-1"> Edit </a>
                                <form class="d-inline" method="POST" action="' . route('transaction.deposit.destroy', $row) . '">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="' . csrf_token() . '" />
                                    <button type="submit" class="btn btn-danger btn-xs px-2 delete-data"> Hapus </button>
                                </form>';
                    }

                    return '<a href="' . route('transaction.deposit.receipt', $row) . '" target="_blank" class="btn btn-secondary btn-xs px-2 mr-1"><i class="fas fa-print"></i> Kwitansi</a>
                        <button type="button" class="btn btn-info btn-xs px-2 mr-1 print-passbook-btn" data-url="' . route('transaction.deposit.passbook', $row) . '" data-title="Simpanan ' . $this->buildTransactionCode($row->id) . '"><i class="fas fa-book"></i> Buku</button>
                        ' . $validateBtn . '
                        <form class="d-inline" method="POST" action="' . route('transaction.deposit.destroy', $row) . '">
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
                ->editColumn('type', function($row) {
                    return $row->type == 'bunga' ? 'Bunga' : 'Simpanan';
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
                    return 'Rp' . number_format($row->amount, 0, ',', '.');
                })
                ->editColumn('keluar', function($row) {
                    return 'Rp0';
                })
                ->editColumn('current_balance', function($row) {
                    return 'Rp' . number_format($row->current_balance, 2, ',', '.');
                })
                ->rawColumns(['action', 'customer'])
                ->make(true);
        }
        return view('pages.transaction.deposit.index', [
            'title' => $this->title,
            'customers' => Customer::all(),
            'types' => ['simpanan' => 'Simpanan', 'bunga' => 'Bunga']
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Ambil dari Manajemen Bunga: jenis 'simpanan'
        $rates = \App\Models\InterestRate::where('type', 'simpanan')
            ->orderBy('effective_date', 'desc')
            ->get();

        // Rate simpanan aktif (terbaru) sebagai default
        $activeRate = $rates->where('is_active', 1)->first();

        return view('pages.transaction.deposit.create', [
            'title' => $this->buildTitle('baru'),
            'customers' => Customer::where('status', 'active')->get(),
            'rates' => $rates,
            'activeRate' => $activeRate,
            'types' => ['simpanan' => 'Simpanan']
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
            $data = $request->except(['interest_rate_id']);
            if ($request->filled('created_at')) {
                $data['created_at'] = Carbon::parse($request->created_at)->setTimeFrom(now());
            }
            $data['previous_balance'] = 0;
            $data['current_balance'] = 0;
            $data['created_by'] = auth()->id();
            
            $deposit = Deposit::create($data);
            Deposit::recalculateBalance($request->customer_id);
            
            if ($request->filled('interest_rate_id')) {
                Customer::where('id', $request->customer_id)->update([
                    'interest_rate_id' => $request->interest_rate_id
                ]);
            }
            
            DB::commit();
            return redirect()->route('transaction.deposit.index')
                ->with('success', 'Berhasil menambahkan simpanan nasabah!')
                ->with('receipt_url', route('transaction.deposit.receipt', $deposit))
                ->with('passbook_url', route('transaction.deposit.passbook', $deposit));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Deposit  $simpanan
     * @return \Illuminate\Http\Response
     */
    public function show(Deposit $simpanan)
    {
        return view('pages.transaction.deposit.show', [
            'title' => $this->buildTitle('detail'),
            'deposit' => $simpanan,
            'code' => $this->buildTransactionCode($simpanan->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Deposit  $simpanan
     * @return \Illuminate\Http\Response
     */
    public function edit(Deposit $simpanan)
    {
        return view('pages.transaction.deposit.edit', [
            'title' => $this->buildTitle('edit'),
            'types' => ['simpanan'],
            'code' => $this->buildTransactionCode($simpanan->id),
            'deposit' => $simpanan,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDepositRequest  $request
     * @param  \App\Models\Deposit  $simpanan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDepositRequest $request, Deposit $simpanan)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['updated_by'] = auth()->id();
            $simpanan->update($data);
            
            Deposit::recalculateBalance($simpanan->customer_id);
            DB::commit();
            
            return back()->with('success', 'Berhasil mengedit simpanan nasabah!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Deposit  $simpanan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Deposit $simpanan)
    {
        try {
            DB::beginTransaction();
            $deletedId = $simpanan->id;
            $customerId = $simpanan->customer_id;
            $simpanan->delete();
            
            Deposit::recalculateBalance($customerId);
            DB::commit();
            
            return back()
                ->with('success', 'Berhasil menghapus simpanan nasabah!')
                ->with('deletion_receipt_url', route('transaction.deposit.destroy-receipt', $deletedId));
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function print(Request $request)
    {
        $customer = Customer::find($request->customer_id);

        $data = Deposit::selectRaw("customer_id, DATE(created_at) as tanggal, SUM(CASE WHEN type='simpanan' THEN amount ELSE 0 END) as simpanan, SUM(CASE WHEN type='bunga' THEN amount ELSE 0 END) as bunga, SUM(CASE WHEN type IN ('simpanan', 'bunga') THEN amount ELSE 0 END) AS saldo")
            ->where('customer_id', $request->customer_id)
            ->whereNot('type', 'penarikan')
            ->groupByRaw('customer_id, DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();
        $manager = User::where('role', 'manager')->first();
        $filename = Carbon::now()->isoFormat('DD-MM-Y') . '_-_laporan_simpanan_nasabah_no_rekening_' . $customer->number  . '_' . time() . '.pdf';

        $pdf = PDF::loadView('pages.transaction.deposit.print', [
            'title' => 'Laporan Simpanan Nasabah',
            'user' => auth()->user(),
            'customer' => $customer,
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data,
            'total' => 0
        ]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    public function receipt(Deposit $simpanan)
    {
        $simpanan->load(['customer', 'creator', 'validator']);
        $code = $this->buildTransactionCode($simpanan->id);
        $terbilang = TerbilangHelper::make($simpanan->amount);

        $pdf = Pdf::loadView('pages.transaction.deposit.receipt', [
            'title' => 'Kwitansi Simpanan ' . $code,
            'deposit' => $simpanan,
            'code' => $code,
            'terbilang' => $terbilang,
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Kwitansi_Simpanan_' . $code . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function validateTransaction(Deposit $simpanan)
    {
        if ($simpanan->validated_at) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah divalidasi sebelumnya oleh ' . ($simpanan->validator->name ?? 'User') . '.',
            ], 422);
        }

        $simpanan->update([
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi simpanan ' . $this->buildTransactionCode($simpanan->id) . ' berhasil divalidasi.',
            'validation_print_url' => route('transaction.deposit.validation-print', $simpanan),
        ]);
    }

    public function printValidation(Deposit $simpanan)
    {
        $simpanan->load(['customer', 'validator']);
        $typeLabel = match($simpanan->type) {
            'bunga' => 'PENYETORAN BUNGA',
            default => 'PENYETORAN TUNAI',
        };

        $validatorName = $simpanan->validator->name ?? auth()->user()->name ?? 'TELLER';
        $validatedAt = $simpanan->validated_at ? $simpanan->validated_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s');

        return view('pages.transaction.validation.print', [
            'title' => 'Cetak Validasi Simpanan - ' . $this->buildTransactionCode($simpanan->id),
            'typeLabel' => $typeLabel,
            'validatorName' => $validatorName,
            'accountNumber' => $simpanan->customer->number ?? '-',
            'validatedAt' => $validatedAt,
            'amount' => $simpanan->amount,
        ]);
    }

    public function destroyReceipt($id)
    {
        $simpanan = Deposit::withTrashed()->with(['customer', 'creator'])->findOrFail($id);
        abort_if(!$simpanan->trashed(), 403, 'Transaksi ini belum dihapus.');

        $code = $this->buildTransactionCode($simpanan->id);
        $terbilang = TerbilangHelper::make($simpanan->amount);

        $pdf = Pdf::loadView('pages.transaction.deposit.destroy-receipt', [
            'title'     => 'Bukti Penghapusan Simpanan ' . $code,
            'deposit'   => $simpanan,
            'code'      => $code,
            'terbilang' => $terbilang,
            'deletedBy' => auth()->user()->name ?? '-',
        ]);
        $pdf->setPaper([0, 0, 609.45, 212.60], 'landscape');

        $filename = 'Bukti_Hapus_Simpanan_' . $code . '_' . time() . '.pdf';
        return $pdf->stream($filename);
    }

    public function passbook(Request $request, Deposit $simpanan)
    {
        $simpanan->load(['customer', 'creator']);
        $startRow = (int) ($request->query('row', 1));
        if ($startRow < 1 || $startRow > 30) {
            $startRow = 1;
        }

        return view('pages.transaction.passbook.print', [
            'title' => 'Cetak Buku Tabungan - ' . ($simpanan->customer->name ?? 'Nasabah'),
            'transactions' => [$simpanan],
            'startRow' => $startRow,
            'customer' => $simpanan->customer,
        ]);
    }

    public function updateBunga(Request $request, \App\Services\SavingsInterestService $service)
    {
        $request->validate([
            'process_date' => 'nullable|date',
        ]);

        try {
            $result = $service->processSavingsInterest(
                $request->process_date,
                auth()->id()
            );

            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses update bunga simpanan: ' . $th->getMessage(),
            ], 500);
        }
    }
}
