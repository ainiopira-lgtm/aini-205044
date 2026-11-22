<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function memberManager(): User
{
    Role::findOrCreate('pustakawan', 'web');
    $user = User::factory()->create();
    $user->assignRole('pustakawan');

    return $user;
}

it('creates an annual member number and a linked account with the member role', function () {
    $this->actingAs(memberManager());

    $this->post(route('admin.members.store'), [
        'name' => 'Siti Aminah',
        'email' => 'siti@example.test',
        'status' => 'active',
    ])->assertRedirect();

    $member = Member::query()->where('email', 'siti@example.test')->firstOrFail();
    expect($member->membership_number)->toMatch('/^AGT-'.now()->format('Y').'-0001$/')
        ->and($member->user->hasRole('anggota'))->toBeTrue();

    $this->post(route('admin.members.store'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.test',
        'status' => 'inactive',
    ])->assertRedirect();

    expect(Member::query()->where('email', 'budi@example.test')->value('membership_number'))
        ->toBe('AGT-'.now()->format('Y').'-0002');
});

it('supports member search, status filtering, edits, and deletion', function () {
    $this->actingAs(memberManager());
    $active = Member::factory()->create(['name' => 'Dewi Aktif', 'status' => 'active']);
    $inactive = Member::factory()->create(['name' => 'Dewi Nonaktif', 'status' => 'inactive']);

    $this->get(route('admin.members.index', ['q' => 'Dewi', 'status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->membership_number)
        ->assertDontSee($active->membership_number);

    $this->put(route('admin.members.update', $active), [
        'name' => 'Dewi Diperbarui',
        'email' => $active->email,
        'status' => 'inactive',
    ])->assertRedirect(route('admin.members.show', $active));

    expect($active->fresh()->status)->toBe('inactive')
        ->and($active->fresh()->user->name)->toBe('Dewi Diperbarui');

    $this->delete(route('admin.members.destroy', $inactive))->assertRedirect(route('admin.members.index'));
    $this->assertDatabaseMissing('members', ['id' => $inactive->id]);
    $this->assertDatabaseMissing('users', ['id' => $inactive->user_id]);
});

it('shows loan history and total fines and generates a PDF membership card', function () {
    Storage::fake('public');
    $this->actingAs(memberManager());
    $member = Member::factory()->create(['photo' => UploadedFile::fake()->image('member.jpg')->store('member-photos', 'public')]);
    $copy = BookCopy::factory()->for(Book::factory())->create();
    $loan = Loan::factory()->for($member)->for($copy)->create();
    Fine::factory()->for($loan)->create(['amount' => 15000]);

    $this->get(route('admin.members.show', $member))
        ->assertOk()
        ->assertSee($copy->book->title)
        ->assertSee('15.000');

    $this->get(route('admin.members.card', $member))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rejects invalid member statuses and protects member management from members', function () {
    $member = User::factory()->create();
    Role::findOrCreate('anggota', 'web');
    $member->assignRole('anggota');
    $this->actingAs($member);

    $this->get(route('admin.members.index'))->assertForbidden();

    $this->actingAs(memberManager())
        ->from(route('admin.members.create'))
        ->post(route('admin.members.store'), [
            'name' => 'Anggota Salah',
            'email' => 'salah@example.test',
            'status' => 'returned',
        ])
        ->assertSessionHasErrors('status');
});
