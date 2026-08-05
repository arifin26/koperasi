<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyInterestAccumulation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'calculation_date' => 'date',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
