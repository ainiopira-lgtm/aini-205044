<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\CirculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

function configureCirculationSettings(array $overrides = []): void
{
    $settings = array_merge([
        'loan_duration_days' => 7,
        'max_active_loans' => 3,
        'fine_per_day' => 1000,
        'max_renewals' => 1,
    ], $overrides);

    foreach ($settings as $key => $value) {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('pustakuku.settings.'.$key);
    }
}

function circulationStaff(): User
{
    Role::findOrCreate('pustakawan', 'web');
    $user = User::factory()->create();
    $user->assignRole('pustakawan');

    return $user;
}

function expectCirculationFailure(callable $operation, string $field): ValidationException
{
    try {
        $operation();
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);

        return $exception;
    }

    throw new RuntimeException('Expected a circulation validation failure.');
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-22 12:00:00'));
    configureCirculationSettings();
});

afterEach(function () {
    $this->travelBack();
});

it('checks out multiple available copies using configurable duration and quota', function () {
    configureCirculationSettings(['loan_duration_days' => 10, 'max_active_loans' => 4]);
    $member = Member::factory()->create();
    $copies = BookCopy::factory()->count(2)->create();
    $staff = circulationStaff();

    $loans = app(CirculationService::class)->checkout($member->id, $copies->modelKeys(), $staff->id);

    expect($loans)->toHaveCount(2)
        ->and($loans->every(fn (Loan $loan) => $loan->due_date->toDateString() === '2026-12-02'))->toBeTrue()
        ->and($member->fresh()->loans()->where('status', 'active')->count())->toBe(2)
        ->and($copies->every(fn (BookCopy $copy) => $copy->fresh()->status === 'borrowed'))->toBeTrue();

    $summary = app(CirculationService::class)->memberSummary($member->id);
    expect($summary['remaining_quota'])->toBe(2)
        ->and($summary['max_active_loans'])->toBe(4);
});

it('rejects inactive members and members with unpaid fines before checkout', function () {
    $service = app(CirculationService::class);
    $staff = circulationStaff();
    $inactive = Member::factory()->create(['status' => 'inactive']);
    $inactiveCopy = BookCopy::factory()->create();

    expectCirculationFailure(
        fn () => $service->checkout($inactive->id, [$inactiveCopy->id], $staff->id),
        'member',
    );
    expect($inactiveCopy->fresh()->status)->toBe('available')
        ->and($inactive->loans()->count())->toBe(0);

    $member = Member::factory()->create();
    $loan = Loan::factory()->for($member)->create();
    Fine::factory()->for($loan)->create(['status' => 'unpaid']);
    $copy = BookCopy::factory()->create();

    expectCirculationFailure(
        fn () => $service->addCopyToDraft($member->id, $copy->barcode),
        'member',
    );
    expectCirculationFailure(
        fn () => $service->checkout($member->id, [$copy->id], $staff->id),
        'member',
    );

    $summary = $service->memberSummary($member->id);
    expect($summary['has_unpaid_fines'])->toBeTrue()
        ->and($summary['unpaid_fine_total'])->toBeGreaterThan(0);
});

it('allows borrowing after fines are paid and enforces the configured loan limit', function () {
    configureCirculationSettings(['max_active_loans' => 2]);
    $member = Member::factory()->create();
    $paidLoan = Loan::factory()->for($member)->create([
        'status' => 'returned',
        'return_date' => today(),
    ]);
    Fine::factory()->for($paidLoan)->create(['status' => 'paid', 'paid_at' => now()]);
    $service = app(CirculationService::class);
    $staff = circulationStaff();
    $firstCopy = BookCopy::factory()->create();

    $service->checkout($member->id, [$firstCopy->id], $staff->id);

    $secondCopy = BookCopy::factory()->create();
    $service->addCopyToDraft($member->id, $secondCopy->barcode);
    $service->checkout($member->id, [$secondCopy->id], $staff->id);

    $thirdCopy = BookCopy::factory()->create();
    expectCirculationFailure(
        fn () => $service->addCopyToDraft($member->id, $thirdCopy->barcode),
        'barcode',
    );
    expectCirculationFailure(
        fn () => $service->checkout($member->id, [$thirdCopy->id], $staff->id),
        'copies',
    );
    expect($member->fresh()->loans()->where('status', 'active')->count())->toBe(2)
        ->and($thirdCopy->fresh()->status)->toBe('available');
});

it('allows exactly three concurrent loans with the default quota', function () {
    $member = Member::factory()->create();
    $copies = BookCopy::factory()->count(4)->create();
    $service = app(CirculationService::class);
    $staffId = circulationStaff()->id;

    $loans = $service->checkout($member->id, $copies->take(3)->modelKeys(), $staffId);
    expect($loans)->toHaveCount(3);

    expectCirculationFailure(
        fn () => $service->checkout($member->id, [$copies[3]->id], $staffId),
        'copies',
    );
    expect($member->loans()->where('status', 'active')->count())->toBe(3)
        ->and($copies[3]->fresh()->status)->toBe('available');
});

it('rolls back every checkout when one scanned copy is not available', function () {
    $member = Member::factory()->create();
    $available = BookCopy::factory()->create();
    $borrowed = BookCopy::factory()->create(['status' => 'borrowed']);
    $staff = circulationStaff();

    expectCirculationFailure(
        fn () => app(CirculationService::class)->checkout($member->id, [$available->id, $borrowed->id], $staff->id),
        'copies',
    );

    expect($member->loans()->count())->toBe(0)
        ->and($available->fresh()->status)->toBe('available')
        ->and($borrowed->fresh()->status)->toBe('borrowed');
});

it('rejects duplicate and unavailable barcode scans in the temporary list', function () {
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create();
    $service = app(CirculationService::class);

    expect($service->addCopyToDraft($member->id, $copy->barcode)->id)->toBe($copy->id);
    expectCirculationFailure(
        fn () => $service->addCopyToDraft($member->id, $copy->barcode, [$copy->id]),
        'barcode',
    );
    expectCirculationFailure(
        fn () => $service->addCopyToDraft($member->id, 'UNKNOWN-CODE'),
        'barcode',
    );
    expectCirculationFailure(
        fn () => $service->checkout($member->id, [], circulationStaff()->id),
        'copies',
    );
});

it('returns on time without a fine and releases the copy', function () {
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'loan_date' => today()->subDays(7),
        'due_date' => today(),
    ]);

    $result = app(CirculationService::class)->returnByBarcode($copy->barcode);

    expect($result['days_late'])->toBe(0)
        ->and($result['fine'])->toBeNull()
        ->and($loan->fresh()->status)->toBe('returned')
        ->and($loan->fresh()->return_date->toDateString())->toBe('2026-11-22')
        ->and($copy->fresh()->status)->toBe('available')
        ->and($loan->fines()->count())->toBe(0);
});

it('calculates late fees using the configured daily fine rate', function () {
    configureCirculationSettings(['fine_per_day' => 2500]);
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'due_date' => today()->subDays(3),
    ]);

    $result = app(CirculationService::class)->returnByBarcode($copy->barcode);

    expect($result['days_late'])->toBe(3)
        ->and((float) $result['fine']->amount)->toBe(7500.0)
        ->and($result['fine']->status)->toBe('unpaid')
        ->and($result['fine']->days_late)->toBe(3)
        ->and($copy->fresh()->status)->toBe('available');
});

it('rejects returning a barcode without an active loan', function () {
    $copy = BookCopy::factory()->create();

    expectCirculationFailure(
        fn () => app(CirculationService::class)->returnByBarcode($copy->barcode),
        'return_barcode',
    );
    expect($copy->fresh()->status)->toBe('available');
});

it('renews an eligible loan once using the configured duration', function () {
    configureCirculationSettings(['loan_duration_days' => 9, 'max_renewals' => 2]);
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'due_date' => today()->addDays(3),
        'extension_count' => 0,
    ]);

    $renewed = app(CirculationService::class)->renewByBarcode($copy->barcode);

    expect($renewed->due_date->toDateString())->toBe('2026-12-04')
        ->and($renewed->extension_count)->toBe(1)
        ->and($loan->fresh()->status)->toBe('active');
});

it('limits renewals to one when using the default setting', function () {
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create(['status' => 'borrowed']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'due_date' => today()->addDay(),
    ]);
    $service = app(CirculationService::class);

    $service->renewByBarcode($copy->barcode);
    expect($loan->fresh()->extension_count)->toBe(1);
    expectCirculationFailure(fn () => $service->renewByBarcode($copy->barcode), 'renew_barcode');
    expect($loan->fresh()->extension_count)->toBe(1);
});

it('blocks renewal for late loans, active reservations, and exhausted renewal counts', function () {
    configureCirculationSettings(['max_renewals' => 2]);
    $service = app(CirculationService::class);
    $member = Member::factory()->create();

    $lateCopy = BookCopy::factory()->create(['status' => 'borrowed']);
    Loan::factory()->for($member)->for($lateCopy)->create([
        'due_date' => today()->subDay(),
        'status' => 'overdue',
    ]);
    expectCirculationFailure(fn () => $service->renewByBarcode($lateCopy->barcode), 'renew_barcode');

    $reservedBook = Book::factory()->create();
    $reservedCopy = BookCopy::factory()->for($reservedBook)->create(['status' => 'borrowed']);
    Loan::factory()->for($member)->for($reservedCopy)->create(['due_date' => today()->addDay()]);
    Reservation::factory()->for($reservedBook)->create(['status' => 'pending', 'expires_at' => now()->addDay()]);
    expectCirculationFailure(fn () => $service->renewByBarcode($reservedCopy->barcode), 'renew_barcode');

    $expiredBook = Book::factory()->create();
    $expiredCopy = BookCopy::factory()->for($expiredBook)->create(['status' => 'borrowed']);
    $expiredLoan = Loan::factory()->for($member)->for($expiredCopy)->create([
        'due_date' => today()->addDay(),
        'extension_count' => 1,
    ]);
    Reservation::factory()->for($expiredBook)->create([
        'status' => 'pending',
        'expires_at' => now()->subDay(),
    ]);
    $renewed = $service->renewByBarcode($expiredCopy->barcode);
    expect($renewed->extension_count)->toBe(2);

    $maxedCopy = BookCopy::factory()->create(['status' => 'borrowed']);
    Loan::factory()->for($member)->for($maxedCopy)->create([
        'due_date' => today()->addDay(),
        'extension_count' => 2,
    ]);
    expectCirculationFailure(fn () => $service->renewByBarcode($maxedCopy->barcode), 'renew_barcode');
    expect($expiredLoan->fresh()->extension_count)->toBe(2);
});

it('rejects missing or invalid rule values instead of using hard-coded defaults', function () {
    Cache::forget('pustakuku.settings.max_active_loans');
    Setting::query()->where('key', 'max_active_loans')->delete();

    expect(fn () => app(CirculationService::class)->memberSummary(Member::factory()->create()->id))
        ->toThrow(LogicException::class);

    Setting::query()->create(['key' => 'max_active_loans', 'value' => '-1']);
    Cache::forget('pustakuku.settings.max_active_loans');
    expect(fn () => app(CirculationService::class)->memberSummary(Member::factory()->create()->id))
        ->toThrow(LogicException::class);
});

it('provides a two-column circulation page and completes its scan workflow', function () {
    $staff = circulationStaff();
    $this->actingAs($staff);
    $member = Member::factory()->create();
    $copy = BookCopy::factory()->create();

    $this->get(route('admin.circulation.index', ['member' => $member->membership_number]))
        ->assertOk()
        ->assertSee('Peminjaman')
        ->assertSee('Pengembalian')
        ->assertSee($member->membership_number);

    $this->post(route('admin.circulation.members.select'), ['member_id' => $member->id])
        ->assertRedirect(route('admin.circulation.index'));
    $this->post(route('admin.circulation.copies.add'), ['barcode' => $copy->barcode])
        ->assertRedirect(route('admin.circulation.index'));
    $this->post(route('admin.circulation.loans.store'))
        ->assertRedirect(route('admin.circulation.index'));

    $loan = $member->loans()->firstOrFail();
    expect($loan->due_date->toDateString())->toBe('2026-11-29')
        ->and($copy->fresh()->status)->toBe('borrowed');

    $this->post(route('admin.circulation.returns.store'), ['return_barcode' => $copy->barcode])
        ->assertRedirect(route('admin.circulation.index'));
    expect($copy->fresh()->status)->toBe('available')
        ->and($loan->fresh()->status)->toBe('returned');
});
