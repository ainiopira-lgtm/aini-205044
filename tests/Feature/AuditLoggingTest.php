<?php

use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;

it('audits create update and delete events for books members and circulation', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $book = Book::factory()->create(['title' => 'Judul Awal']);
    $book->update(['title' => 'Judul Baru']);
    $book->delete();

    $member = Member::factory()->create(['name' => 'Nama Awal']);
    $member->update(['name' => 'Nama Baru']);
    $member->update(['email' => 'anggota-baru@example.test']);

    $copy = BookCopy::factory()->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create();
    $fine = Fine::factory()->for($loan)->create();
    $loan->update(['status' => 'returned', 'return_date' => today()]);
    $copy->update(['status' => 'available']);
    $fine->update(['status' => 'paid', 'paid_at' => now()]);
    $loan->delete();
    $member->delete();

    foreach ([
        [Book::class, $book->id, ['book.created', 'book.updated', 'book.deleted']],
        [Member::class, $member->id, ['member.created', 'member.updated', 'member.updated', 'member.deleted']],
        [Loan::class, $loan->id, ['loan.created', 'loan.updated', 'loan.deleted']],
        [BookCopy::class, $copy->id, ['bookcopy.created', 'bookcopy.updated']],
        [Fine::class, $fine->id, ['fine.created', 'fine.updated']],
    ] as [$subjectType, $subjectId, $actions]) {
        expect(ActivityLog::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('user_id', $actor->id)
            ->orderBy('id')
            ->pluck('action')
            ->all())->toBe($actions);
    }

    $memberUpdate = ActivityLog::query()
        ->where('subject_type', Member::class)
        ->where('subject_id', $member->id)
        ->where('action', 'member.updated')
        ->latest('id')
        ->firstOrFail();
    expect($memberUpdate->properties['email'])->toBe('[redacted]');
});
