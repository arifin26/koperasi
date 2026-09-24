<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestRate extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'interest_rate_id');
    }

    public function fixedDeposits()
    {
        return $this->hasMany(FixedDeposit::class, 'interest_rate_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSavings($query)
    {
        return $query->where('type', 'simpanan');
    }

    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposito');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
