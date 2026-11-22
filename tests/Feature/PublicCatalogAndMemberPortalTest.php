<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use Spatie\Permission\Models\Role;

function portalMember(): Member
{
    Role::findOrCreate('anggota', 'web');
    $member = Member::factory()->create();
    $member->user->assignRole('anggota');

    return $member;
}

it('shows recent books on the homepage and supports public catalog filters', function () {
    $category = Category::factory()->create(['name' => 'Sains Terapan']);
    $author = Author::factory()->create(['name' => 'Dina Penulis']);
    $matchingBook = Book::factory()->create([
        'title' => 'Mencari Pola Data',
        'category_id' => $category->id,
        'year' => 2020,
    ]);
    $matchingBook->authors()->attach($author);
    BookCopy::factory()->for($matchingBook)->create();

    $otherBook = Book::factory()->create([
        'title' => 'Pola Berbeda',
        'category_id' => Category::factory()->create(['name' => 'Seni'])->id,
        'year' => 2018,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Satu buku bisa membuka dunia baru.')
        ->assertSee('Mencari Pola Data');

    $this->get(route('catalog.index', [
        'q' => 'Mencari',
        'category_id' => $category->id,
        'author_id' => $author->id,
        'year' => 2020,
    ]))
        ->assertOk()
        ->assertSee('Mencari Pola Data')
        ->assertDontSee('Pola Berbeda');
});

it('shows book details and the exact available-copy count publicly', function () {
    $book = Book::factory()->create(['title' => 'Buku Detail']);
    BookCopy::factory()->for($book)->create(['status' => 'available']);
    BookCopy::factory()->for($book)->create(['status' => 'borrowed']);
    BookCopy::factory()->for($book)->create(['status' => 'damaged']);

    $this->get(route('catalog.show', $book))
        ->assertOk()
        ->assertSee('Buku Detail')
        ->assertSee('1 dari 3')
        ->assertDontSee('Reservasi buku');
});

it('shows only the signed-in member loans, due dates, history, fines, and reservations', function () {
    $member = portalMember();
    $ownCopy = BookCopy::factory()->create();
    $ownLoan = Loan::factory()->for($member)->for($ownCopy)->create([
        'due_date' => today()->addDays(2),
    ]);
    Fine::factory()->for($ownLoan)->create(['amount' => 12000]);

    $historyCopy = BookCopy::factory()->create();
    Loan::factory()->for($member)->for($historyCopy)->create([
        'status' => 'returned',
        'return_date' => today()->subDay(),
    ]);

    $otherMember = Member::factory()->create();
    $otherCopy = BookCopy::factory()->create();
    Loan::factory()->for($otherMember)->for($otherCopy)->create();

    $this->actingAs($member->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('member.dashboard'));

    $this->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee($ownCopy->book->title)
        ->assertSee($historyCopy->book->title)
        ->assertSee('Jatuh tempo')
        ->assertSee('Riwayat pinjam')
        ->assertSee('Daftar denda')
        ->assertSee('12.000')
        ->assertDontSee($otherCopy->book->title);
});

it('allows members to renew only their own eligible loans', function () {
    $member = portalMember();
    $ownCopy = BookCopy::factory()->create(['status' => 'borrowed']);
    $ownLoan = Loan::factory()->for($member)->for($ownCopy)->create([
        'due_date' => today()->addDay(),
    ]);
    $otherMember = Member::factory()->create();
    $otherCopy = BookCopy::factory()->create(['status' => 'borrowed']);
    $otherLoan = Loan::factory()->for($otherMember)->for($otherCopy)->create([
        'due_date' => today()->addDay(),
    ]);

    $this->actingAs($member->user)
        ->post(route('member.loans.renew', $ownLoan))
        ->assertRedirect(route('member.dashboard'));

    expect($ownLoan->fresh()->extension_count)->toBe(1)
        ->and($ownLoan->fresh()->due_date->toDateString())->toBe(today()->addDays(8)->toDateString());

    $this->from(route('member.dashboard'))
        ->post(route('member.loans.renew', $otherLoan))
        ->assertRedirect(route('member.dashboard'))
        ->assertSessionHasErrors('loan');

    expect($otherLoan->fresh()->extension_count)->toBe(0);
});
