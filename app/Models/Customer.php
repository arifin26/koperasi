<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $guarded = [];

    public function interestRate()
    {
        return $this->belongsTo(InterestRate::class, 'interest_rate_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function fixedDeposits()
    {
        return $this->hasMany(FixedDeposit::class);
    }

    public function collaterals()
    {
        return $this->hasMany(Collateral::class);
    }

    public function foreclosures()
    {
        return $this->hasMany(Foreclosure::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
