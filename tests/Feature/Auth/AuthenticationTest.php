<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login is rate limited after five failed attempts', function () {
    RateLimiter::clear('person@example.test|127.0.0.1');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => 'person@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => 'person@example.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(RateLimiter::tooManyAttempts('person@example.test|127.0.0.1', 5))->toBeTrue();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
