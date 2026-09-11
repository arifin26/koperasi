<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->title = 'Nasabah';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Customer::query())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('fixed-deposit.create', ['customer_id' => $row->id]) . '" class="btn btn-info btn-xs px-2"> Deposito </a>
                            <a href="' . route('customer.show', $row) . '" class="btn btn-success btn-xs px-2"> Detail </a>
                            <a href="' . route('customer.edit', $row) . '" class="btn btn-primary btn-xs px-2 mx-1"> Edit </a>
                            <form class="d-inline" method="POST" action="' . route('customer.destroy', $row) . '">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="_token" value="' . csrf_token() . '" />
                                <button type="submit" class="btn btn-danger btn-xs px-2 delete-data"> Hapus </button>
                            </form>';
                })
                ->editColumn('joined_at', function($row) {
                    return Carbon::parse($row->joined_at)->isoFormat('DD-MM-Y');
                })
                ->editColumn('status', function($row) {
                    if ($row->status == 'blacklist') {
                        return '<span class="badge d-block p-2 badge-danger">Blacklist</span>';
                    }
                    return '<span class="badge d-block p-2 badge-success">Active</span>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view('pages.customer.index', [
            'title' => $this->title
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.customer.create', [
            'title' => $this->buildTitle('baru')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCustomerRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->except('amount');
            $data['photo'] = $this->storeImage($request);
            $data['joined_at'] = now();
            Customer::create($data);

            DB::commit();
            return redirect()->route('customer.index')->with('success', 'Berhasil menambahkan nasabah!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $nasabah
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $nasabah)
    {
        $balances = Deposit::recalculateBalance($nasabah->id);
        
        return view('pages.customer.show', [
            'title' => $this->buildTitle('detail'),
            'user' => $nasabah,
            'balances' => $balances
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $nasabah
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $nasabah)
    {
        return view('pages.customer.edit', [
            'title' => $this->buildTitle('edit'),
            'user' => $nasabah
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCustomerRequest  $request
     * @param  \App\Models\Customer  $nasabah
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, Customer $nasabah)
    {
        try {
            $data = $request->except('photo');
            $data['photo'] = $this->updateImage($request, $nasabah->photo);
            $nasabah->update($data);
            return back()->with('success', 'Berhasil mengedit nasabah!');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $nasabah
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $nasabah)
    {
        try {
            DB::beginTransaction();
            // $this->deleteImage($nasabah->photo); // Do not delete photo on soft delete
            $nasabah->visits()->delete();
            $nasabah->foreclosures()->delete();
            $nasabah->deposits()->delete();
            $nasabah->collaterals()->delete();
            $nasabah->delete();
            DB::commit();
            return back()->with('success', 'Berhasil menghapus nasabah!');
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

        $data = Customer::whereBetween('joined_at', $filter)->orderBy('joined_at')->get();
        $manager = User::where('role', 'manager')->first();
        $filename = Carbon::now()->isoFormat('DD-MM-Y') . '_-_laporan_data_nasabah_periode_' . $time_from . '_-_' . $time_to  . '_' . time() . '.pdf';

        $pdf = PDF::loadView('pages.customer.print', [
            'title' => 'Laporan Data Nasabah',
            'user' => auth()->user(),
            'filter' => "$time_from sampai $time_to",
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $manager,
            'data' => $data
        ]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    public function currentBalanceByDeposit($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $balances = Deposit::recalculateBalance($id);
            $saldo = (int) ($balances['simpanan'] ?? 0);

            $depositCount = Deposit::where('customer_id', $id)->count();
            $hasDeposit = $depositCount > 0;

            // NEW: Check for active/extended fixed deposits
            $activeFixedDeposit = FixedDeposit::where('customer_id', $id)
                ->whereIn('status', ['active', 'extended'])
                ->whereNull('deleted_at')
                ->first();
            $hasActiveDeposit = $activeFixedDeposit !== null;
            $activeDepositNumber = $hasActiveDeposit ? $activeFixedDeposit->number : null;

            $data = Deposit::where('customer_id', $id)->latest()->first();
            if ($data) {
                $data->current_balance = $saldo;
                $data->current_balance_formatted = 'Rp' . number_format($data->current_balance, 2, ',', '.');
                $data->has_deposit = $hasDeposit;
                $data->deposit_count = $depositCount;
                $data->customer_name = $customer->name;
                $data->customer_number = $customer->number;
                $data->has_active_deposit = $hasActiveDeposit;
                $data->active_deposit_number = $activeDepositNumber;
            } else {
                $data = new \stdClass();
                $data->current_balance = $saldo;
                $data->current_balance_formatted = 'Rp' . number_format($data->current_balance, 2, ',', '.');
                $data->has_deposit = $hasDeposit;
                $data->deposit_count = $depositCount;
                $data->customer_name = $customer->name;
                $data->customer_number = $customer->number;
                $data->has_active_deposit = $hasActiveDeposit;
                $data->active_deposit_number = $activeDepositNumber;
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'has_deposit' => $hasDeposit,
                'deposit_count' => $depositCount,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Data nasabah tidak ditemukan.'
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => $th->getCode() ?: 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $search = $request->q;
        $customers = Customer::where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%")
                      ->orWhere('number', 'LIKE', "%$search%");
            })
            ->limit(20)
            ->get(['id', 'name', 'number']);

        $formatted = $customers->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->number . ' - ' . $item->name
            ];
        });

        return response()->json([
            'results' => $formatted
        ]);
    }

    public function passbook(Request $request, Customer $nasabah)
    {
        $startRow = (int) ($request->query('row', 1));
        if ($startRow < 1 || $startRow > 30) {
            $startRow = 1;
        }

        $query = Deposit::where('customer_id', $nasabah->id)
            ->with(['customer', 'creator'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($request->ajax() || $request->wantsJson() || $request->query('format') === 'json') {
            $allTransactions = $query->get();
            return response()->json([
                'status' => 'success',
                'customer' => [
                    'id' => $nasabah->id,
                    'name' => $nasabah->name,
                    'number' => $nasabah->number,
                ],
                'transactions' => $allTransactions->map(function ($txn, $idx) {
                    $isDebit = ($txn->type === 'penarikan');
                    $notesLower = strtolower($txn->notes ?? '');
                    if ($isDebit) {
                        $code = '2';
                    } elseif ($txn->type === 'bunga') {
                        $code = str_contains($notesLower, 'deposito') ? '3' : '4';
                    } else {
                        $code = '1';
                    }

                    return [
                        'id' => $txn->id,
                        'row_num' => $idx + 1,
                        'date' => \Carbon\Carbon::parse($txn->created_at)->format('d/m/y'),
                        'code' => $code,
                        'type_label' => $isDebit ? 'Penarikan' : ($txn->type === 'bunga' ? 'Bunga' : 'Setoran'),
                        'debit' => $isDebit ? number_format($txn->amount, 0, ',', '.') : '-',
                        'credit' => !$isDebit ? number_format($txn->amount, 0, ',', '.') : '-',
                        'balance' => number_format($txn->current_balance, 0, ',', '.'),
                    ];
                })
            ]);
        }

        if ($request->filled('transaction_ids')) {
            $ids = is_array($request->transaction_ids)
                ? $request->transaction_ids
                : explode(',', $request->transaction_ids);
            $query->whereIn('id', $ids);
        }

        $transactions = $query->get();

        return view('pages.transaction.passbook.print', [
            'title' => 'Cetak Buku Tabungan - ' . $nasabah->name,
            'transactions' => $transactions,
            'startRow' => $startRow,
            'customer' => $nasabah,
        ]);
    }
}
