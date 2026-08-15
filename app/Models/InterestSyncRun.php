<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestSyncRun extends Model
{
    protected $guarded = ["id"];

    protected $casts = [
        "sync_from_date" => "date",
        "sync_to_date" => "date",
        "started_at" => "datetime",
        "completed_at" => "datetime",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where("status", "pending");
    }

    public function scopeRunning($query)
    {
        return $query->where("status", "running");
    }

    public function scopeSuccess($query)
    {
        return $query->where("status", "success");
    }

    public function scopeFailed($query)
    {
        return $query->where("status", "failed");
    }
}
