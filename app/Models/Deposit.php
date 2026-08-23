<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Hitung ulang saldo berjalan nasabah dari seluruh riwayat transaksinya.
     *
     * Hanya ada satu kantong saldo: setoran 'simpanan' dan 'bunga' menambah,
     * 'penarikan' mengurangi.
     *
     * @return array{simpanan: int} saldo akhir
     */
    public static function recalculateBalance($customerId)
    {
        $transactions = self::where('customer_id', $customerId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $balance = 0;

        foreach ($transactions as $transaction) {
            $transaction->previous_balance = $balance;

            if ($transaction->type === 'penarikan') {
                $balance -= $transaction->amount;
            } else {
                $balance += $transaction->amount;
            }

            $transaction->current_balance = $balance;
            $transaction->saveQuietly();
        }

        return ['simpanan' => $balance];
    }
}
