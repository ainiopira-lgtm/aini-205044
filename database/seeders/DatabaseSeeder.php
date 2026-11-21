<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            'admin' => [
                'name' => 'Administrator PustakaKu',
                'email' => 'admin@pustakaku.test',
            ],
            'pustakawan' => [
                'name' => 'Pustakawan PustakaKu',
                'email' => 'pustakawan@pustakaku.test',
            ],
            'anggota' => [
                'name' => 'Anggota PustakaKu',
                'email' => 'anggota@pustakaku.test',
            ],
        ];

        foreach ($accounts as $roleName => $attributes) {
            Role::findOrCreate($roleName, 'web');

            $user = User::updateOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles($roleName);
        }

        $this->call(LibrarySeeder::class);
    }
}
