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
        $count = 0;
        Loan::query()
            ->where('status', 'active')
            ->whereNull('return_date')
            ->where('due_date', '<', today()->toDateString())
            ->chunkById(100, function ($loans) use (&$count): void {
                foreach ($loans as $loan) {
                    $loan->update(['status' => 'overdue']);
                    $count++;
                }
            });

        $this->info("{$count} loan ditandai terlambat.");

        return self::SUCCESS;
    }
}
