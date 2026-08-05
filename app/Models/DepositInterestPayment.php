<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositInterestPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function fixedDeposit()
    {
        return $this->belongsTo(FixedDeposit::class);
    }

    public function savingsTransaction()
    {
        return $this->belongsTo(Deposit::class, 'savings_txn_id');
    }
}
