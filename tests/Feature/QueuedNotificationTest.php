<?php

use App\Mail\LoanDueReminderMail;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->travelTo(Carbon\Carbon::parse('2026-11-22 12:00:00'));
});

afterEach(function () {
    $this->travelBack();
});

it('queues one email reminder for loans due tomorrow and does not duplicate it', function () {
    Mail::fake();
    $member = Member::factory()->create(['email' => 'due@example.test']);
    $book = Book::factory()->create(['title' => 'Buku Jatuh Tempo']);
    $copy = BookCopy::factory()->for($book)->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'due_date' => today()->addDay(),
        'status' => 'active',
    ]);

    $this->artisan('loans:send-due-reminders')
        ->expectsOutput('1 pengingat jatuh tempo masuk antrean.')
        ->assertExitCode(0);

    expect($loan->fresh()->reminder_sent_at)->not->toBeNull();
    Mail::assertQueued(LoanDueReminderMail::class);
    $queuedMail = Mail::queued(LoanDueReminderMail::class)->first();
    expect($queuedMail->bookTitle)->toBe('Buku Jatuh Tempo')
        ->and($queuedMail->memberName)->toBe($member->name)
        ->and($queuedMail->dueDate)->toBe('23/11/2026');

    $this->artisan('loans:send-due-reminders')
        ->expectsOutput('0 pengingat jatuh tempo masuk antrean.')
        ->assertExitCode(0);
    Mail::assertQueued(LoanDueReminderMail::class, 1);
});

it('does not queue reminders for returned loans or loans without a member email', function () {
    Mail::fake();
    $memberWithoutEmail = Member::factory()->create(['email' => null]);
    $book = Book::factory()->create();
    $copy = BookCopy::factory()->for($book)->create(['status' => 'borrowed']);
    Loan::factory()->for($memberWithoutEmail)->for($copy)->create([
        'due_date' => today()->addDay(),
        'status' => 'active',
    ]);

    $returnedMember = Member::factory()->create();
    $returnedCopy = BookCopy::factory()->for($book)->create(['status' => 'available']);
    Loan::factory()->for($returnedMember)->for($returnedCopy)->create([
        'due_date' => today()->addDay(),
        'return_date' => today(),
        'status' => 'returned',
    ]);

    $this->artisan('loans:send-due-reminders')
        ->expectsOutput('0 pengingat jatuh tempo masuk antrean.')
        ->expectsOutput('1 pinjaman dilewati karena anggota tidak memiliki email.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});
