<?php

use App\Models\ActivityLog;
use App\Models\AuthorBook;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;

it('creates circulation and activity records through their model relationships', function () {
    $loan = Loan::factory()->create();
    Fine::factory()->for($loan)->create();
    $activity = ActivityLog::factory()
        ->for($loan->user)
        ->create([
            'subject_type' => Loan::class,
            'subject_id' => $loan->id,
        ]);
    $authorBook = AuthorBook::factory()->create();

    expect($loan->member)->toBeInstanceOf(Member::class)
        ->and($loan->bookCopy->book)->toBeInstanceOf(Book::class)
        ->and($loan->user)->toBeInstanceOf(User::class)
        ->and($loan->fines)->toHaveCount(1)
        ->and($activity->subject)->toBeInstanceOf(Loan::class)
        ->and($authorBook->exists)->toBeTrue();
});
