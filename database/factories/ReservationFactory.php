<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Member;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reservation> */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'book_id' => Book::factory(),
            'reserved_at' => now(),
            'expires_at' => now()->addDays(7),
            'fulfilled_at' => null,
            'status' => 'pending',
        ];
    }
}
