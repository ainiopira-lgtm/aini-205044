<?php

use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use App\Services\LibrarySettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

function finesSettingsAdmin(): User
{
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function finesSettingsLibrarian(): User
{
    Role::findOrCreate('pustakawan', 'web');
    $user = User::factory()->create();
    $user->assignRole('pustakawan');

    return $user;
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-22 12:00:00'));
});

afterEach(function () {
    $this->travelBack();
});

it('filters fines by payment status', function () {
    $unpaid = Fine::factory()->create(['amount' => 12000]);
    $paid = Fine::factory()->create([
        'amount' => 7000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs(finesSettingsLibrarian())
        ->get(route('admin.fines.index', ['status' => 'unpaid']))
        ->assertOk()
        ->assertSee($unpaid->loan->member->membership_number)
        ->assertDontSee($paid->loan->member->membership_number);

    $this->get(route('admin.fines.index', ['status' => 'paid']))
        ->assertOk()
        ->assertSee($paid->loan->member->membership_number)
        ->assertDontSee($unpaid->loan->member->membership_number);
});

it('records a fine payment once and generates a small PDF receipt', function () {
    $fine = Fine::factory()->create();
    $this->actingAs(finesSettingsLibrarian());

    $this->post(route('admin.fines.pay', $fine))
        ->assertRedirect(route('admin.fines.index'));

    $fine->refresh();
    expect($fine->status)->toBe('paid')
        ->and($fine->paid_at->toDateTimeString())->toBe('2026-11-22 12:00:00');

    $this->get(route('admin.fines.index'))
        ->assertOk()
        ->assertSee(route('admin.fines.receipt', $fine));

    $receipt = $this->get(route('admin.fines.receipt', $fine));
    $receipt->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($receipt->getContent(), 0, 4))->toBe('%PDF');

    $this->from(route('admin.fines.index'))
        ->post(route('admin.fines.pay', $fine))
        ->assertRedirect(route('admin.fines.index'))
        ->assertSessionHasErrors('fine');
});

it('does not print a payment receipt before a fine is paid', function () {
    $fine = Fine::factory()->create(['status' => 'unpaid', 'paid_at' => null]);

    $this->actingAs(finesSettingsLibrarian())
        ->get(route('admin.fines.receipt', $fine))
        ->assertNotFound();
});

it('allows only administrators to view and update circulation settings', function () {
    $this->actingAs(finesSettingsLibrarian())
        ->get(route('admin.settings.edit'))
        ->assertForbidden();

    $admin = finesSettingsAdmin();
    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Lama pinjam (hari)')
        ->assertSee('Maksimal buku per anggota');

    Cache::put('pustakuku.settings.loan_duration_days', '99', now()->addHour());
    $this->get(route('admin.settings.edit'))->assertSee('value="99"', false);

    $this->put(route('admin.settings.update'), [
        'loan_duration_days' => 14,
        'max_active_loans' => 5,
        'max_renewals' => 2,
        'fine_per_day' => 2500,
    ])->assertRedirect(route('admin.settings.edit'));

    $this->assertDatabaseHas('settings', ['key' => 'loan_duration_days', 'value' => '14']);
    $this->assertDatabaseHas('settings', ['key' => 'max_active_loans', 'value' => '5']);
    $this->assertDatabaseHas('settings', ['key' => 'max_renewals', 'value' => '2']);
    $this->assertDatabaseHas('settings', ['key' => 'fine_per_day', 'value' => '2500']);
    expect(app(LibrarySettingsService::class)->getInt('loan_duration_days'))->toBe(14);

    $this->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), [
            'loan_duration_days' => 0,
            'max_active_loans' => 0,
            'max_renewals' => -1,
            'fine_per_day' => -100,
        ])
        ->assertSessionHasErrors([
            'loan_duration_days',
            'max_active_loans',
            'max_renewals',
            'fine_per_day',
        ]);
});

it('marks only active loans past due as overdue and registers the daily schedule', function () {
    $pastDue = Loan::factory()->create([
        'status' => 'active',
        'due_date' => today()->subDay(),
        'return_date' => null,
    ]);
    $dueToday = Loan::factory()->create([
        'status' => 'active',
        'due_date' => today(),
        'return_date' => null,
    ]);
    $alreadyReturned = Loan::factory()->create([
        'status' => 'returned',
        'due_date' => today()->subDay(),
        'return_date' => today()->subDay(),
    ]);
    $inconsistentReturned = Loan::factory()->create([
        'status' => 'active',
        'due_date' => today()->subDay(),
        'return_date' => today(),
    ]);

    $this->artisan('loans:mark-overdue')
        ->expectsOutput('1 loan ditandai terlambat.')
        ->assertExitCode(0);

    expect($pastDue->fresh()->status)->toBe('overdue')
        ->and($dueToday->fresh()->status)->toBe('active')
        ->and($alreadyReturned->fresh()->status)->toBe('returned')
        ->and($inconsistentReturned->fresh()->status)->toBe('active');

    $this->artisan('loans:mark-overdue')
        ->expectsOutput('0 loan ditandai terlambat.')
        ->assertExitCode(0);

    $this->artisan('schedule:list')
        ->expectsOutputToContain('loans:mark-overdue')
        ->assertExitCode(0);
});
