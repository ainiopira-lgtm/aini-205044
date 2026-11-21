<?php

namespace Database\Factories;

use App\Models\Shelf;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shelf> */
class ShelfFactory extends Factory
{
    protected $model = Shelf::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SH-###'),
            'name' => 'Rak '.fake()->unique()->numerify('###'),
            'location' => fake()->optional()->word(),
        ];
    }
}
