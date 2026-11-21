<?php

namespace Database\Factories;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fine> */
class FineFactory extends Factory
{
    protected $model = Fine::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => fake()->randomFloat(2, 1, 100),
            'days_late' => fake()->numberBetween(1, 30),
            'status' => 'unpaid',
            'paid_at' => null,
        ];
    }
}
