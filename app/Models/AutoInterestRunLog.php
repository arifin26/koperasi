<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoInterestRunLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'savings_result' => 'array',
        'deposit_result' => 'array',
        'triggered_at' => 'datetime',
    ];

    /**
     * Scope: Get log for a specific period (Y-m format)
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope: Get successful runs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Get failed runs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get pending runs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if a period was already successfully processed
     */
    public static function isProcessedFor(string $period): bool
    {
        return static::forPeriod($period)->successful()->exists();
    }

    /**
     * Check if a period has a stale pending run (older than 5 minutes)
     */
    public static function hasStalePendingFor(string $period): bool
    {
        return static::forPeriod($period)
            ->pending()
            ->where('triggered_at', '<', now()->subMinutes(5))
            ->exists();
    }

    /**
     * Get or create a pending log for the period
     */
    public static function getOrCreatePending(string $period): self
    {
        return static::updateOrCreate(
            ['period' => $period],
            [
                'status' => 'pending',
                'triggered_at' => now(),
                'triggered_by' => 'auto',
            ]
        );
    }

    /**
     * Mark as success with results
     */
    public function markSuccess(array $savingsResult, array $depositResult): self
    {
        $this->update([
            'status' => 'success',
            'savings_result' => $savingsResult,
            'deposit_result' => $depositResult,
            'error_message' => null,
        ]);

        return $this;
    }

    /**
     * Mark as failed with error message
     */
    public function markFailed(string $errorMessage): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        return $this;
    }
}
