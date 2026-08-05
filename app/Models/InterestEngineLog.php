<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestEngineLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'run_date' => 'date',
    ];
}
