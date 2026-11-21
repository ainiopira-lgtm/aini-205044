<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\AuthorBook;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuthorBook> */
class AuthorBookFactory extends Factory
{
    protected $model = AuthorBook::class;

    public function definition(): array
    {
        return [
            'author_id' => Author::factory(),
            'book_id' => Book::factory(),
        ];
    }
}
