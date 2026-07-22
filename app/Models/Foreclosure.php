<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foreclosure extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function collateral()
    {
        return $this->belongsTo(Collateral::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

}
