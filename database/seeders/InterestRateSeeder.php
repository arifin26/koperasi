<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InterestRate;

class InterestRateSeeder extends Seeder
{
    public function run()
    {
        $rates = [
            ['type' => 'simpanan', 'rate_percent' => 4.00, 'effective_date' => '2026-01-01', 'is_active' => 1, 'is_default' => 1],
            ['type' => 'deposito', 'rate_percent' => 6.00, 'effective_date' => '2026-01-01', 'is_active' => 1],
        ];

        foreach ($rates as $rate) {
            InterestRate::create($rate);
        }
    }
}
