<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CirculationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'loan_duration_days' => 7,
            'max_active_loans' => 3,
            'max_renewals' => 1,
            'fine_per_day' => 1000,
        ] as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }
    }
}
