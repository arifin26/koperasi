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
                    return '<a href="' . route('customer.show', $row) . '" class="btn btn-success btn-xs px-2"> Detail </a>
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
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }

    public function currentBalanceByDeposit($id)
    {
        try {
            $balances = Deposit::recalculateBalance($id);
            $saldo = $balances['simpanan'] ?? 0;
            
            $data = Deposit::where('customer_id', $id)->latest()->first();
            if ($data) {
                $data->current_balance = $saldo;
                $data->current_balance_formatted = 'Rp' . number_format($data->current_balance, 2, ',', '.');
            } else {
                $data = new \stdClass();
                $data->current_balance = $saldo;
                $data->current_balance_formatted = 'Rp' . number_format($data->current_balance, 2, ',', '.');
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ]);
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
}
