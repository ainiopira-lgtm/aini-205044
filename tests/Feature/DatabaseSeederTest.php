<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CirculationSettingsSeeder;
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
    expect(Setting::query()->where('key', 'loan_duration_days')->value('value'))->toBe('7');
});

it('initializes circulation settings without overwriting existing values', function () {
    $this->seed(CirculationSettingsSeeder::class);
    Setting::query()->where('key', 'fine_per_day')->update(['value' => '1500']);

    $this->seed(CirculationSettingsSeeder::class);

    expect(Setting::query()->where('key', 'loan_duration_days')->value('value'))->toBe('7');
    expect(Setting::query()->where('key', 'fine_per_day')->value('value'))->toBe('1500');
});
