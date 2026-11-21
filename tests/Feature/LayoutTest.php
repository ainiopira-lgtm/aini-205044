<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('shows admin navigation to administrators and hides restricted entries from members', function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('anggota', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Katalog buku')
        ->assertSee('Pengaturan')
        ->assertSee('Koleksi terbaru')
        ->assertSee('PustakaKu');

    $member = User::factory()->create();
    $member->assignRole('anggota');

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Layanan anggota')
        ->assertDontSee('Pengaturan')
        ->assertDontSee('Katalog buku');
});

it('renders the public layout on the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Temukan bacaan berikutnya.')
        ->assertSee('Masuk ke perpustakaan')
        ->assertSee('PustakaKu');
});
