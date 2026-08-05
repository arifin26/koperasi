<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InterestRate;

class InterestRateSeeder extends Seeder
{
    public function run()
    {
        $rates = [
            ['type' => 'tabungan_sukarela', 'rate_percent' => 4.00, 'effective_date' => '2026-01-01', 'is_active' => 1],
            ['type' => 'tabungan_wajib', 'rate_percent' => 3.50, 'effective_date' => '2026-01-01', 'is_active' => 1],
            ['type' => 'deposito_3_bulan', 'rate_percent' => 5.00, 'effective_date' => '2026-01-01', 'is_active' => 1],
            ['type' => 'deposito_6_bulan', 'rate_percent' => 5.50, 'effective_date' => '2026-01-01', 'is_active' => 1],
            ['type' => 'deposito_12_bulan', 'rate_percent' => 6.00, 'effective_date' => '2026-01-01', 'is_active' => 1],
        ];

        foreach ($rates as $rate) {
            InterestRate::create($rate);
        }
    }
}
