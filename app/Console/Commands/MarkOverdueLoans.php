<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class MarkOverdueLoans extends Command
{
    protected $signature = 'loans:mark-overdue';

    protected $description = 'Mark active loans past their due date as overdue';

    public function handle(): int
    {
        $count = Loan::query()
            ->where('status', 'active')
            ->whereNull('return_date')
            ->where('due_date', '<', today()->toDateString())
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

        $this->info("{$count} loan ditandai terlambat.");

        return self::SUCCESS;
    }
}
