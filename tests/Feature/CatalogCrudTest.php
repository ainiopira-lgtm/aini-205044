<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function catalogStaff(string $role): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows librarians to create, search, update, and delete catalog reference data', function () {
    $this->actingAs(catalogStaff('pustakawan'));

    $resources = [
        'categories' => [
            ['name' => 'Fiksi', 'description' => 'Cerita rekaan'],
            ['name' => 'Fiksi populer', 'description' => 'Cerita pilihan'],
        ],
        'authors' => [
            ['name' => 'Ayu Utami', 'bio' => 'Penulis Indonesia'],
            ['name' => 'Ayu Utami Baru', 'bio' => 'Biografi diperbarui'],
        ],
        'publishers' => [
            ['name' => 'Penerbit Satu', 'email' => 'satu@example.test', 'phone' => '021123', 'address' => 'Jakarta'],
            ['name' => 'Penerbit Baru', 'email' => 'baru@example.test', 'phone' => '021456', 'address' => 'Bandung'],
        ],
        'shelves' => [
            ['code' => 'R-A1', 'name' => 'Rak A1', 'location' => 'Lantai 1'],
            ['code' => 'R-B2', 'name' => 'Rak B2', 'location' => 'Lantai 2'],
        ],
    ];

    foreach ($resources as $resource => [$createData, $updateData]) {
        $this->get(route("admin.{$resource}.create"))->assertOk();
        $storeResponse = $this->post(route("admin.{$resource}.store"), $createData);
        $item = match ($resource) {
            'categories' => Category::query()->where('name', $createData['name'])->firstOrFail(),
            'authors' => Author::query()->where('name', $createData['name'])->firstOrFail(),
            'publishers' => Publisher::query()->where('name', $createData['name'])->firstOrFail(),
            'shelves' => Shelf::query()->where('code', $createData['code'])->firstOrFail(),
        };

        $storeResponse->assertRedirect(route("admin.{$resource}.show", $item));
        $this->get(route("admin.{$resource}.show", $item))->assertOk();
        $this->get(route("admin.{$resource}.edit", $item))->assertOk();
        $this->get(route("admin.{$resource}.index", ['q' => substr($createData['name'] ?? $createData['code'], 0, 5)]))
            ->assertOk()
            ->assertSee($createData['name'] ?? $createData['code']);

        $this->put(route("admin.{$resource}.update", $item), $updateData)
            ->assertRedirect(route("admin.{$resource}.show", $item));
        $this->assertDatabaseHas($item->getTable(), [
            'id' => $item->id,
            $resource === 'shelves' ? 'code' : 'name' => $resource === 'shelves' ? $updateData['code'] : $updateData['name'],
        ]);
        $this->delete(route("admin.{$resource}.destroy", $item))->assertRedirect(route("admin.{$resource}.index"));
        $this->assertDatabaseMissing($item->getTable(), ['id' => $item->id]);
    }
});

it('creates and updates books, manages cover files, authors, and copies', function () {
    Storage::fake('public');
    $this->actingAs(catalogStaff('admin'));
    $this->get(route('admin.books.create'))->assertOk();

    $category = Category::factory()->create();
    $publisher = Publisher::factory()->create();
    $shelf = Shelf::factory()->create();
    $authors = Author::factory()->count(2)->create();
    $data = [
        'title' => 'Bumi Manusia',
        'isbn' => '9789799731234',
        'category_id' => $category->id,
        'publisher_id' => $publisher->id,
        'shelf_id' => $shelf->id,
        'year' => 1980,
        'synopsis' => 'Kisah tentang perjalanan hidup.',
        'price' => 125000,
        'authors' => $authors->modelKeys(),
        'cover' => UploadedFile::fake()->image('cover.png'),
    ];

    $this->post(route('admin.books.store'), $data)->assertRedirect();
    $book = Book::query()->where('isbn', $data['isbn'])->firstOrFail();
    $this->get(route('admin.books.show', $book))->assertOk();
    $this->get(route('admin.books.edit', $book))->assertOk();
    $this->get(route('admin.books.index', ['q' => 'Bumi', 'category_id' => $category->id]))
        ->assertOk()
        ->assertSee($book->title);
    $this->assertDatabaseHas('author_book', ['book_id' => $book->id, 'author_id' => $authors[0]->id]);
    Storage::disk('public')->assertExists($book->cover);

    $oldCover = $book->cover;
    $replacementAuthor = Author::factory()->create();
    $updatedData = [...$data, 'title' => 'Bumi Manusia (Edisi Baru)', 'authors' => [$replacementAuthor->id], 'cover' => UploadedFile::fake()->image('new-cover.jpg')];

    $this->put(route('admin.books.update', $book), $updatedData)
        ->assertRedirect(route('admin.books.show', $book));
    $this->assertDatabaseHas('activity_logs', [
        'subject_type' => Book::class,
        'subject_id' => $book->id,
        'action' => 'book.authors_updated',
    ]);

    $book->refresh();
    expect($book->title)->toBe('Bumi Manusia (Edisi Baru)')
        ->and($book->authors->modelKeys())->toBe([$replacementAuthor->id])
        ->and($book->cover)->not->toBe($oldCover);
    Storage::disk('public')->assertMissing($oldCover);
    Storage::disk('public')->assertExists($book->cover);

    $this->post(route('admin.books.copies.store', $book))->assertRedirect(route('admin.books.show', $book));
    $copy = $book->copies()->firstOrFail();
    expect($copy->barcode)->toMatch('/^PKU-\d{5,}$/');

    $this->patch(route('admin.books.copies.update', [$book, $copy]), ['status' => 'damaged'])
        ->assertRedirect(route('admin.books.show', $book));
    expect($copy->fresh()->status)->toBe('damaged');

    $this->get(route('admin.books.show', $book))->assertOk()->assertSee($copy->barcode);

    $this->delete(route('admin.books.copies.destroy', [$book, $copy]))
        ->assertRedirect(route('admin.books.show', $book));
    $this->assertDatabaseMissing('book_copies', ['id' => $copy->id]);

    $finalCover = $book->cover;
    $this->delete(route('admin.books.destroy', $book))->assertRedirect(route('admin.books.index'));
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
    Storage::disk('public')->assertMissing($finalCover);
});

it('rejects invalid cover files and prevents members from managing the catalog', function () {
    $member = catalogStaff('anggota');
    $this->actingAs($member);

    $this->get(route('admin.categories.index'))->assertForbidden();
    $this->get(route('admin.books.create'))->assertForbidden();

    $category = Category::factory()->create();
    $publisher = Publisher::factory()->create();
    $shelf = Shelf::factory()->create();
    $author = Author::factory()->create();

    $this->post(route('admin.books.store'), [
        'title' => 'Buku Tidak Valid',
        'category_id' => $category->id,
        'publisher_id' => $publisher->id,
        'shelf_id' => $shelf->id,
        'year' => 2020,
        'synopsis' => 'Sinopsis',
        'price' => 10000,
        'authors' => [$author->id],
        'cover' => UploadedFile::fake()->create('dokumen.txt', 20, 'text/plain'),
    ])->assertForbidden();

    $validData = [
        'title' => 'Buku Dengan Sampul Salah',
        'category_id' => $category->id,
        'publisher_id' => $publisher->id,
        'shelf_id' => $shelf->id,
        'year' => 2020,
        'synopsis' => 'Sinopsis',
        'price' => 10000,
        'authors' => [$author->id],
        'cover' => UploadedFile::fake()->create('dokumen.txt', 20, 'text/plain'),
    ];

    $this->actingAs(catalogStaff('admin'))
        ->from(route('admin.books.create'))
        ->post(route('admin.books.store'), $validData)
        ->assertSessionHasErrors('cover');

    $validData['cover'] = UploadedFile::fake()->image('terlalu-besar.jpg')->size(2049);
    $this->from(route('admin.books.create'))
        ->post(route('admin.books.store'), $validData)
        ->assertSessionHasErrors('cover');
});

it('prevents deleting catalog records referenced by books', function () {
    $this->actingAs(catalogStaff('admin'));
    $category = Category::factory()->create();
    $book = Book::factory()->create(['category_id' => $category->id]);

    $this->delete(route('admin.categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
    $this->assertDatabaseHas('books', ['id' => $book->id]);
});
