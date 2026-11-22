<?php

use App\Exports\ReportExport;
use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

it('imports valid book rows and reports the row numbers and errors for invalid rows', function () {
    Role::findOrCreate('pustakawan', 'web');
    $librarian = User::factory()->create();
    $librarian->assignRole('pustakawan');
    $category = Category::factory()->create();
    $publisher = Publisher::factory()->create();
    $shelf = Shelf::factory()->create();
    $author = Author::factory()->create();

    $csv = implode("\n", [
        'title,isbn,category_id,publisher_id,shelf_id,year,synopsis,price,author_ids',
        sprintf('"  Buku Impor  ",9781111111111,%d,%d,%d,2020,"Sinopsis buku",12500,%d', $category->id, $publisher->id, $shelf->id, $author->id),
        sprintf('"Baris Rusak",9782222222222,999999,%d,%d,900,"Sinopsis",0,999999', $publisher->id, $shelf->id),
        sprintf('"ISBN Duplikat",9781111111111,%d,%d,%d,2021,"Sinopsis",0,', $category->id, $publisher->id, $shelf->id),
    ]);

    $response = $this->actingAs($librarian)
        ->post(route('admin.books.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('books.csv', $csv),
        ])
        ->assertRedirect(route('admin.books.import.create'))
        ->assertSessionHas('import_report', function (array $report): bool {
            expect($report['failures'][0]['errors'])->not->toBeEmpty();

            return $report['imported'] === 1
                && count($report['failures']) === 2
                && $report['failures'][0]['row'] === 3
                && $report['failures'][1]['row'] === 4;
        });

    $this->assertDatabaseHas('books', [
        'title' => 'Buku Impor',
        'isbn' => '9781111111111',
        'cover' => '',
    ]);
    $this->assertDatabaseHas('author_book', [
        'author_id' => $author->id,
        'book_id' => DB::table('books')->where('isbn', '9781111111111')->value('id'),
    ]);

    $this->get(route('admin.books.import.create'))
        ->assertOk()
        ->assertSee('1 buku berhasil diimpor')
        ->assertSee('2 baris gagal')
        ->assertSee('Baris Rusak');
});

it('validates uploaded file type and restricts imports to catalog staff', function () {
    $this->get(route('admin.books.import.create'))->assertRedirect(route('login'));

    Role::findOrCreate('anggota', 'web');
    $member = User::factory()->create();
    $member->assignRole('anggota');
    $this->actingAs($member)
        ->get(route('admin.books.import.create'))
        ->assertForbidden();

    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)
        ->from(route('admin.books.import.create'))
        ->post(route('admin.books.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('books.pdf', 'not an import file'),
        ])
        ->assertRedirect(route('admin.books.import.create'))
        ->assertSessionHasErrors('file');
});

it('imports an Excel workbook as well as CSV files', function () {
    Role::findOrCreate('pustakawan', 'web');
    $librarian = User::factory()->create();
    $librarian->assignRole('pustakawan');
    $category = Category::factory()->create();
    $publisher = Publisher::factory()->create();
    $shelf = Shelf::factory()->create();

    $fileContents = Excel::raw(new ReportExport(
        ['title', 'isbn', 'category_id', 'publisher_id', 'shelf_id', 'year', 'synopsis', 'price', 'author_ids'],
        [['Buku Workbook', '9783333333333', $category->id, $publisher->id, $shelf->id, 2022, 'Sinopsis', 0, '']],
    ), ExcelFormat::XLSX);

    $this->actingAs($librarian)
        ->post(route('admin.books.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('books.xlsx', $fileContents),
        ])
        ->assertRedirect(route('admin.books.import.create'))
        ->assertSessionHas('import_report', fn (array $report): bool => $report['imported'] === 1 && $report['failures'] === []);

    $this->assertDatabaseHas('books', [
        'title' => 'Buku Workbook',
        'isbn' => '9783333333333',
    ]);
});
