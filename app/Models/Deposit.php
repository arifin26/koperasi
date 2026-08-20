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

    public static function recalculateBalance($customerId)
    {
        $transactions = self::where('customer_id', $customerId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalances = [
            'pokok' => 0,
            'wajib' => 0,
            'sukarela' => 0,
            'bunga' => 0,
        ];

        foreach ($transactions as $transaction) {
            $type = $transaction->type;
            
            if ($type === 'penarikan') {
                $transaction->previous_balance = $runningBalances['sukarela'];
                $runningBalances['sukarela'] -= $transaction->amount;
                $transaction->current_balance = $runningBalances['sukarela'];
            } else {
                $transaction->previous_balance = $runningBalances[$type] ?? 0;
                $runningBalances[$type] = ($runningBalances[$type] ?? 0) + $transaction->amount;
                $transaction->current_balance = $runningBalances[$type];
            }

            $transaction->saveQuietly();
        }
        
        return $runningBalances;
    }
}
