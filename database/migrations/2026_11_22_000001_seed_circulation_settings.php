<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('settings')->insertOrIgnore([
            ['key' => 'loan_duration_days', 'value' => '7', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'max_active_loans', 'value' => '3', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fine_per_day', 'value' => '1000', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'max_renewals', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'loan_duration_days',
            'max_active_loans',
            'fine_per_day',
            'max_renewals',
        ])->delete();
    }
};
