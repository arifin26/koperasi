<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestPostingLog extends Model
{
    protected $guarded = ['id'];

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
