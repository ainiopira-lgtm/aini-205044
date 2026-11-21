<?php

namespace Database\Factories;

use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Loan> */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $loanDate = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'member_id' => Member::factory(),
            'book_copy_id' => BookCopy::factory(),
            'user_id' => User::factory(),
            'loan_date' => $loanDate,
            'due_date' => (clone $loanDate)->modify('+14 days'),
            'return_date' => null,
            'extension_count' => 0,
            'status' => 'active',
        ];
    }
}
