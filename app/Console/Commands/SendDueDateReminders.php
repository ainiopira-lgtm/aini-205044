<?php

namespace App\Console\Commands;

use App\Mail\LoanDueReminderMail;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDueDateReminders extends Command
{
    protected $signature = 'loans:send-due-reminders';

    protected $description = 'Queue email reminders for loans due tomorrow';

    public function handle(): int
    {
        $loans = Loan::query()
            ->with([
                'member:id,name,email',
                'bookCopy:id,book_id',
                'bookCopy.book:id,title',
            ])
            ->where('status', 'active')
            ->whereNull('return_date')
            ->whereNull('reminder_sent_at')
            ->whereDate('due_date', today()->addDay())
            ->orderBy('id')
            ->get();
        $queued = 0;
        $withoutEmail = 0;

        foreach ($loans as $candidate) {
            $email = $candidate->member->email;
            if (blank($email)) {
                $withoutEmail++;

                continue;
            }

            $didQueue = DB::transaction(function () use ($candidate, $email): bool {
                $loan = Loan::query()->whereKey($candidate->id)->lockForUpdate()->first();
                if (
                    ! $loan
                    || $loan->status !== 'active'
                    || $loan->return_date !== null
                    || $loan->reminder_sent_at !== null
                    || ! $loan->due_date->isTomorrow()
                ) {
                    return false;
                }

                Mail::to($email)->queue(new LoanDueReminderMail(
                    $candidate->member->name,
                    $candidate->bookCopy->book->title,
                    $loan->due_date->format('d/m/Y'),
                ));
                $loan->update(['reminder_sent_at' => now()]);

                return true;
            });

            $queued += (int) $didQueue;
        }

        $this->info("{$queued} pengingat jatuh tempo masuk antrean.");
        if ($withoutEmail > 0) {
            $this->warn("{$withoutEmail} pinjaman dilewati karena anggota tidak memiliki email.");
        }

        return self::SUCCESS;
    }
}
