<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\User;
use App\Services\CirculationService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

function reservationMember(): Member
{
    Role::findOrCreate('anggota', 'web');
    $member = Member::factory()->create();
    $member->user->assignRole('anggota');

    return $member;
}

function expectReservationFailure(callable $operation): ValidationException
{
    try {
        $operation();
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('reservation');

        return $exception;
    }

    throw new RuntimeException('Expected a reservation validation failure.');
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-22 12:00:00'));
});

afterEach(function () {
    $this->travelBack();
});

it('allows reservations only when every copy is borrowed and prevents active duplicates', function () {
    $member = reservationMember();
    $book = Book::factory()->create();
    $initialCopy = BookCopy::factory()->for($book)->create(['status' => 'available']);

    expectReservationFailure(fn () => app(ReservationService::class)->reserve($member->id, $book->id));
    expect($member->reservations()->count())->toBe(0);

    $bookWithoutCopies = Book::factory()->create();
    expectReservationFailure(fn () => app(ReservationService::class)->reserve($member->id, $bookWithoutCopies->id));

    $initialCopy->update(['status' => 'borrowed']);
    $copies = BookCopy::factory()->for($book)->count(2)->create(['status' => 'borrowed']);
    $reservation = app(ReservationService::class)->reserve($member->id, $book->id);

    expect($reservation->status)->toBe('pending')
        ->and($reservation->reserved_at->toDateTimeString())->toBe('2026-11-22 12:00:00');
    expectReservationFailure(fn () => app(ReservationService::class)->reserve($member->id, $book->id));
    expect($copies)->toHaveCount(2);
});

it('promotes reservations in FIFO order and expires ready holds after two days', function () {
    $book = Book::factory()->create();
    $copies = BookCopy::factory()->for($book)->count(2)->create(['status' => 'borrowed']);
    $firstMember = reservationMember();
    $secondMember = reservationMember();
    $first = app(ReservationService::class)->reserve($firstMember->id, $book->id);
    $this->travel(1)->seconds();
    $second = app(ReservationService::class)->reserve($secondMember->id, $book->id);

    foreach ($copies as $copy) {
        Loan::factory()->for($firstMember)->for($copy)->create([
            'due_date' => today(),
        ]);
        app(CirculationService::class)->returnByBarcode($copy->barcode);
        $this->travel(1)->seconds();
    }

    expect($first->fresh()->status)->toBe('ready')
        ->and($first->fresh()->expires_at->toDateTimeString())->toBe('2026-11-24 12:00:01')
        ->and($second->fresh()->status)->toBe('ready');

    $bookWithQueue = Book::factory()->create();
    $availableCopy = BookCopy::factory()->for($bookWithQueue)->create(['status' => 'available']);
    $expiredMember = reservationMember();
    $waitingMember = reservationMember();
    $expired = Reservation::factory()->for($expiredMember)->for($bookWithQueue)->create([
        'status' => 'ready',
        'expires_at' => now()->subSecond(),
    ]);
    $waiting = Reservation::factory()->for($waitingMember)->for($bookWithQueue)->create([
        'status' => 'pending',
        'expires_at' => null,
    ]);

    app(ReservationService::class)->promoteAvailableQueue($bookWithQueue->id);

    expect($expired->fresh()->status)->toBe('expired')
        ->and($waiting->fresh()->status)->toBe('ready')
        ->and($waiting->fresh()->expires_at->toDateTimeString())->toBe(now()->addDays(2)->toDateTimeString())
        ->and($availableCopy->fresh()->status)->toBe('available');
});

it('holds a returned copy for the ready member and fulfills that reservation at checkout', function () {
    $book = Book::factory()->create();
    $copy = BookCopy::factory()->for($book)->create(['status' => 'borrowed']);
    $borrower = reservationMember();
    $waitingMember = reservationMember();
    Loan::factory()->for($borrower)->for($copy)->create(['due_date' => today()]);
    $reservation = app(ReservationService::class)->reserve($waitingMember->id, $book->id);
    app(CirculationService::class)->returnByBarcode($copy->barcode);
    $staff = User::factory()->create();
    $otherMember = reservationMember();

    try {
        app(CirculationService::class)->checkout($otherMember->id, [$copy->id], $staff->id);
        test()->fail('An unreserved member must not take a held copy.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('copies');
    }
    expect($copy->fresh()->status)->toBe('available');

    app(CirculationService::class)->checkout($waitingMember->id, [$copy->id], $staff->id);

    expect($copy->fresh()->status)->toBe('borrowed')
        ->and($reservation->fresh()->status)->toBe('fulfilled')
        ->and($reservation->fresh()->fulfilled_at)->not->toBeNull();
});

it('creates reservations from the book page only for authenticated members', function () {
    $member = reservationMember();
    $book = Book::factory()->create();
    BookCopy::factory()->for($book)->create(['status' => 'borrowed']);

    $this->get(route('catalog.show', $book))
        ->assertOk()
        ->assertSee('Masuk sebagai anggota untuk reservasi');

    $this->actingAs($member->user)
        ->get(route('catalog.show', $book))
        ->assertOk()
        ->assertSee('Reservasi buku');

    $this->post(route('catalog.reservations.store', $book))
        ->assertRedirect(route('catalog.show', $book));
    $this->assertDatabaseHas('reservations', [
        'member_id' => $member->id,
        'book_id' => $book->id,
        'status' => 'pending',
    ]);
});
