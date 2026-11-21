<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'isbn' => fake()->unique()->isbn13(),
            'category_id' => Category::factory(),
            'publisher_id' => Publisher::factory(),
            'shelf_id' => Shelf::factory(),
            'year' => fake()->numberBetween(1950, (int) date('Y')),
            'synopsis' => fake()->paragraph(),
            'cover' => 'covers/'.fake()->uuid().'.jpg',
            'price' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
