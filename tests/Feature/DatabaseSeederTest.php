<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('seeds the three PustakaKu roles and their example users', function () {
    $this->seed();

    $expectedAccounts = [
        'admin' => 'admin@pustakaku.test',
        'pustakawan' => 'pustakawan@pustakaku.test',
        'anggota' => 'anggota@pustakaku.test',
    ];

    expect(Role::query()->whereIn('name', array_keys($expectedAccounts))->count())->toBe(3);

    foreach ($expectedAccounts as $role => $email) {
        expect(User::role($role)->value('email'))->toBe($email);
    }

    $this->seed();

    expect(User::query()->whereIn('email', array_values($expectedAccounts))->count())->toBe(3);
    expect(Book::query()->count())->toBe(50);
    expect(BookCopy::query()->count())->toBe(150);
    expect(Book::query()->with(['category', 'publisher', 'shelf', 'authors', 'copies'])->first()?->authors)->not->toBeEmpty();
});
