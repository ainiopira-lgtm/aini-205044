<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookCopyRequest;
use App\Http\Requests\UpdateBookCopyRequest;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookCopyController extends Controller
{
    public function store(StoreBookCopyRequest $request, Book $book): RedirectResponse
    {
        $copy = DB::transaction(function () use ($book) {
            $copy = $book->copies()->create([
                'barcode' => 'TMP-'.Str::uuid(),
                'status' => 'available',
                'condition' => 'good',
            ]);
            $copy->update(['barcode' => sprintf('PKU-%05d', $copy->id)]);

            return $copy;
        });

        return to_route('admin.books.show', $book)->with('success', "Eksemplar {$copy->barcode} berhasil ditambahkan.");
    }

    public function update(UpdateBookCopyRequest $request, Book $book, BookCopy $copy): RedirectResponse
    {
        abort_unless($copy->book_id === $book->id, 404);
        $copy->update($request->validated());

        return to_route('admin.books.show', $book)->with('success', 'Status eksemplar berhasil diperbarui.');
    }

    public function destroy(Book $book, BookCopy $copy): RedirectResponse
    {
        $this->authorize('update', $book);
        abort_unless($copy->book_id === $book->id, 404);

        if ($copy->loans()->exists()) {
            return back()->with('error', 'Eksemplar yang sudah tercatat dalam peminjaman tidak dapat dihapus.');
        }

        $copy->delete();

        return to_route('admin.books.show', $book)->with('success', 'Eksemplar berhasil dihapus.');
    }
}
