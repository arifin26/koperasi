<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Libur mingguan koperasi: hanya hari Minggu. Sabtu termasuk hari kerja.
     *
     * Satu-satunya tempat aturan ini ditulis — engine bunga, penghitung hari
     * kerja setahun, dan endpoint cek tanggal semuanya lewat sini.
     */
    public static function isWeeklyOff(Carbon $date): bool
    {
        return $date->isSunday();
    }

    /**
     * Hari kerja = bukan Minggu dan bukan hari libur, kecuali ada baris
     * Holiday bertipe 'workday' yang menimpanya (hari kerja pengganti).
     */
    public static function isWorkday(Carbon $date): bool
    {
        $holiday = static::whereDate('date', $date->format('Y-m-d'))->first();

        if ($holiday) {
            return $holiday->type === 'workday';
        }

        return !static::isWeeklyOff($date);
    }

    /**
     * Hari kerja pertama pada atau setelah $date.
     *
     * Dipakai untuk menentukan tanggal posting bunga simpanan: tanggal 1 bulan
     * berikutnya, atau hari kerja sesudahnya kalau tanggal 1 jatuh di hari
     * Minggu / hari merah.
     */
    public static function firstWorkdayOnOrAfter(Carbon $date): Carbon
    {
        $candidate = $date->copy()->startOfDay();

        // Batas 31 hari supaya tidak pernah jadi loop tak berujung kalau data
        // hari libur salah input sebulan penuh.
        for ($i = 0; $i < 31; $i++) {
            if (static::isWorkday($candidate)) {
                return $candidate;
            }

            $candidate->addDay();
        }

        return $date->copy()->startOfDay();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
