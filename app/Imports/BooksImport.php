<?php

namespace App\Imports;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class BooksImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    /**
     * @var list<array{row: int, title: string, isbn: string, errors: list<string>}>
     */
    private array $failures = [];

    private int $importedCount = 0;

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        if (collect($data)->every(fn ($value): bool => blank($value))) {
            return;
        }

        $authorIds = collect(explode('|', (string) ($data['author_ids'] ?? '')))
            ->map(fn (string $id): string => trim($id))
            ->filter(fn (string $id): bool => $id !== '')
            ->values()
            ->all();
        $validationData = [
            ...$data,
            'title' => trim((string) ($data['title'] ?? '')),
            'isbn' => blank($data['isbn'] ?? null) ? null : trim((string) $data['isbn']),
            'synopsis' => trim((string) ($data['synopsis'] ?? '')),
            'author_ids_array' => $authorIds,
        ];
        $validator = Validator::make($validationData, [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255', Rule::unique('books', 'isbn')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'year' => ['required', 'integer', 'between:1000,'.((int) now()->format('Y') + 1)],
            'synopsis' => ['required', 'string', 'max:20000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'author_ids' => ['nullable', 'string', 'max:1000'],
            'author_ids_array' => ['array'],
            'author_ids_array.*' => ['integer', 'distinct', 'exists:authors,id'],
        ], [], [
            'category_id' => 'ID kategori',
            'publisher_id' => 'ID penerbit',
            'shelf_id' => 'ID rak',
            'author_ids_array.*' => 'ID penulis',
        ]);

        if ($validator->fails()) {
            $this->failures[] = [
                'row' => $row->getIndex(),
                'title' => $validationData['title'],
                'isbn' => $validationData['isbn'] ?? '',
                'errors' => $validator->errors()->all(),
            ];

            return;
        }

        $valid = $validator->validated();
        DB::transaction(function () use ($valid, $authorIds): void {
            $book = Book::query()->create([
                'title' => $valid['title'],
                'isbn' => $valid['isbn'] ?? null,
                'category_id' => $valid['category_id'],
                'publisher_id' => $valid['publisher_id'],
                'shelf_id' => $valid['shelf_id'],
                'year' => $valid['year'],
                'synopsis' => $valid['synopsis'],
                'cover' => '',
                'price' => $valid['price'] ?? 0,
            ]);
            $book->authors()->sync(array_map('intval', $authorIds));
        });

        $this->importedCount++;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @return list<array{row: int, title: string, isbn: string, errors: list<string>}>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}
