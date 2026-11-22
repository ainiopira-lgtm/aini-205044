<?php

use App\Exports\ReportExport;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-22 12:00:00'));
});

afterEach(function () {
    $this->travelBack();
});

it('exports formula-leading strings as literal text in Excel reports', function () {
    $export = new ReportExport(['Nilai'], [
        ['=HYPERLINK("https://example.test","open")'],
        ["\t+1+1"],
        ['@SUM(A1:A2)'],
        [1200],
    ]);

    expect($export->array())->toBe([
        ["'=HYPERLINK(\"https://example.test\",\"open\")"],
        ["'\t+1+1"],
        ["'@SUM(A1:A2)"],
        [1200],
    ]);
});

it('shows admin dashboard statistics, popular books, chart data, and loans due today', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $dueTodayBook = Book::factory()->create(['title' => 'Buku Jatuh Tempo Hari Ini']);
    $dueTodayCopy = BookCopy::factory()->for($dueTodayBook)->create(['status' => 'borrowed']);
    $activeMember = Member::factory()->create(['status' => 'active']);
    $dueTodayLoan = Loan::factory()->for($activeMember)->for($dueTodayCopy)->create([
        'loan_date' => today()->subDays(3),
        'due_date' => today(),
        'status' => 'active',
    ]);
    Fine::factory()->for($dueTodayLoan)->create([
        'amount' => 12000,
        'created_at' => now(),
    ]);

    $overdueBook = Book::factory()->create(['title' => 'Buku Terlambat']);
    $overdueCopy = BookCopy::factory()->for($overdueBook)->create(['status' => 'borrowed']);
    $overdueMember = Member::factory()->create(['status' => 'active']);
    Loan::factory()->for($overdueMember)->for($overdueCopy)->create([
        'loan_date' => today()->subDays(10),
        'due_date' => today()->subDay(),
        'status' => 'overdue',
    ]);
    Member::factory()->create(['status' => 'returned']);

    $response = $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total buku')
        ->assertSee('Anggota aktif')
        ->assertSee('Sedang dipinjam')
        ->assertSee('Terlambat')
        ->assertSee('Denda bulan ini')
        ->assertSee('Rp 12.000')
        ->assertSee('Peminjaman 30 hari terakhir')
        ->assertSee('Buku Jatuh Tempo Hari Ini')
        ->assertSee('Buku Terlambat')
        ->assertSee('data-labels=', false)
        ->assertSee('data-values=', false);

    $response->assertSeeTextInOrder([
        'Total buku',
        '2',
        'Anggota aktif',
        '2',
        'Sedang dipinjam',
        '1',
        'Terlambat',
        '1',
        'Denda bulan ini',
        'Rp 12.000',
    ]);
});

it('filters transaction reports by the requested dates and shows the current inventory', function () {
    Role::findOrCreate('pustakawan', 'web');
    $librarian = User::factory()->create();
    $librarian->assignRole('pustakawan');

    $member = Member::factory()->create(['name' => 'Anggota Periode']);
    $book = Book::factory()->create(['title' => 'Buku Dalam Periode']);
    $copy = BookCopy::factory()->for($book)->create(['status' => 'available']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'loan_date' => '2026-11-10',
        'due_date' => '2026-11-17',
        'return_date' => '2026-11-18',
        'status' => 'returned',
    ]);
    Fine::factory()->for($loan)->create([
        'amount' => 1500,
        'created_at' => '2026-11-19 10:00:00',
    ]);

    $outsideMember = Member::factory()->create(['name' => 'Anggota Di Luar Periode']);
    $outsideBook = Book::factory()->create(['title' => 'Buku Di Luar Periode']);
    $outsideCopy = BookCopy::factory()->for($outsideBook)->create(['status' => 'borrowed']);
    Loan::factory()->for($outsideMember)->for($outsideCopy)->create([
        'loan_date' => '2026-10-30',
        'due_date' => '2026-11-06',
    ]);

    $this->actingAs($librarian)
        ->get(route('admin.reports.index', [
            'report' => 'loans',
            'from' => '2026-11-01',
            'to' => '2026-11-30',
        ]))
        ->assertOk()
        ->assertSee('Buku Dalam Periode')
        ->assertDontSee('Buku Di Luar Periode');

    $this->get(route('admin.reports.index', [
        'report' => 'returns',
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]))
        ->assertOk()
        ->assertSee('Buku Dalam Periode')
        ->assertDontSee('Buku Di Luar Periode');

    $this->get(route('admin.reports.index', [
        'report' => 'fines',
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]))
        ->assertOk()
        ->assertSee('Anggota Periode')
        ->assertDontSee('Anggota Di Luar Periode');

    $this->get(route('admin.reports.index', [
        'report' => 'popular',
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]))
        ->assertOk()
        ->assertSee('Buku Dalam Periode')
        ->assertDontSee('Buku Di Luar Periode');

    $this->get(route('admin.reports.index', [
        'report' => 'inventory',
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]))
        ->assertOk()
        ->assertSee('Buku Dalam Periode')
        ->assertSee('Buku Di Luar Periode');
});

it('exports every report to PDF and Excel', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = Member::factory()->create();
    $book = Book::factory()->create(['title' => 'Buku Ekspor']);
    $copy = BookCopy::factory()->for($book)->create(['status' => 'available']);
    $loan = Loan::factory()->for($member)->for($copy)->create([
        'loan_date' => '2026-11-10',
        'due_date' => '2026-11-17',
        'return_date' => '2026-11-18',
        'status' => 'returned',
    ]);
    Fine::factory()->for($loan)->create(['created_at' => '2026-11-19 10:00:00']);

    $this->actingAs($admin);
    $filters = [
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ];

    foreach (['loans', 'returns', 'fines', 'popular', 'inventory'] as $reportType) {
        $pdf = $this->get(route('admin.reports.export', $filters + [
            'format' => 'pdf',
            'report' => $reportType,
        ]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');

        $this->get(route('admin.reports.export', $filters + [
            'format' => 'xlsx',
            'report' => $reportType,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
});

it('restricts reports to authenticated admin staff roles', function () {
    $this->get(route('admin.reports.index'))->assertRedirect(route('login'));

    Role::findOrCreate('anggota', 'web');
    $member = User::factory()->create();
    $member->assignRole('anggota');

    $this->actingAs($member)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
