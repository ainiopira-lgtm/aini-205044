<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        if (Book::query()->exists()) {
            return;
        }

        $categories = Category::factory()->count(8)->create();
        $authors = Author::factory()->count(20)->create();
        $publishers = Publisher::factory()->count(8)->create();
        $shelves = Shelf::factory()->count(12)->create();

        $books = Book::factory()->count(50)->create([
            'category_id' => fn () => $categories->random()->id,
            'publisher_id' => fn () => $publishers->random()->id,
            'shelf_id' => fn () => $shelves->random()->id,
        ]);

        foreach ($books as $book) {
            $book->authors()->attach($authors->random(fake()->numberBetween(1, 3))->modelKeys());
            BookCopy::factory()->count(3)->for($book)->create();
        }
    }
}
