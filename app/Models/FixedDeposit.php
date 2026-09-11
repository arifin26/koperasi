<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class FixedDeposit extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = [
        'number',
        'account_number',
        'customer_id',
        'interest_rate_id',
        'amount',
        'tenor_months',
        'rate_percent',
        'start_date',
        'maturity_date',
        'status',
        'extended_from_id',
        'liquidated_at',
        'matured_at',
        'notes',
        'created_by',
        'updated_by',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'maturity_date' => 'date',
        'liquidated_at' => 'datetime',
        'matured_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function interestRate()
    {
        return $this->belongsTo(InterestRate::class, 'interest_rate_id');
    }

    public function interestPayments()
    {
        return $this->hasMany(DepositInterestPayment::class);
    }

    public function extendedFrom()
    {
        return $this->belongsTo(FixedDeposit::class, 'extended_from_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by')->withTrashed();
    }

    public function getIsValidatedAttribute()
    {
        return !is_null($this->validated_at);
    }

    /**
     * Generate nomor deposito unik: DEP-YYYYMM-NNNNN
     */
    public static function generateNumber()
    {
        $prefix = 'DEP-' . date('Ym') . '-';
        $lastDeposit = self::withTrashed()
            ->where('number', 'like', $prefix . '%')
            ->orderBy('number', 'desc')
            ->first();

        if ($lastDeposit) {
            $lastSeq = (int) substr($lastDeposit->number, -5);
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        return $prefix . str_pad($newSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung bunga bulanan: FLOOR(nominal * rate / 12)
     */
    public function getMonthlyInterestAttribute()
    {
        return (int) floor($this->amount * ($this->rate_percent / 100) / 12);
    }

    /**
     * Cek apakah sudah jatuh tempo
     */
    public function getIsMaturedAttribute()
    {
        return Carbon::today()->gte($this->maturity_date);
    }

    /**
     * Label status yang ramah user
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'active' => '<span class="badge badge-success">Aktif</span>',
            'matured' => '<span class="badge badge-warning">Jatuh Tempo</span>',
            'extended' => '<span class="badge badge-info">Diperpanjang</span>',
            'liquidated' => '<span class="badge badge-secondary">Dicairkan</span>',
            default => '<span class="badge badge-dark">-</span>',
        };
    }
}
