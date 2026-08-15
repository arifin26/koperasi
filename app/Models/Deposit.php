<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        "customer_id",
        "type",
        "amount",
        "previous_balance",
        "current_balance",
        "notes",
        "is_system_generated",
        "period",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope: Get only system-generated deposits (bunga transactions)
     */
    public function scopeSystemGenerated($query)
    {
        return $query->where("is_system_generated", true);
    }

    /**
     * Scope: Get only user-created deposits
     */
    public function scopeUserCreated($query)
    {
        return $query->where("is_system_generated", false);
    }

    /**
     * Scope: Get deposits by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("type", $type);
    }

    /**
     * Recalculate running balances for a customer
     * Single parameter: customer_id
     */
    public static function recalculateBalance($customerId)
    {
        $transactions = self::where("customer_id", $customerId)
            ->orderBy("created_at", "asc")
            ->orderBy("id", "asc")
            ->get();

        $runningBalances = [
            "pokok" => 0,
            "wajib" => 0,
            "sukarela" => 0,
            "bunga" => 0,
            "bunga_deposito" => 0,
        ];

        foreach ($transactions as $transaction) {
            $type = $transaction->type;

            if ($type === "penarikan") {
                $transaction->previous_balance = $runningBalances["sukarela"];
                $runningBalances["sukarela"] -= $transaction->amount;
                $transaction->current_balance = $runningBalances["sukarela"];
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
