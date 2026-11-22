<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $categoryId = $request->input('category_id');

        $books = Book::query()
            ->with(['authors', 'category', 'publisher', 'shelf'])
            ->withCount('copies')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%")
                ->orWhereHas('authors', fn (Builder $authors) => $authors->where('name', 'like', "%{$search}%"))))
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('books.index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'search' => $search,
            'categoryId' => $categoryId,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Book::class);

        return view('books.create', $this->formData());
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $coverPath = $this->storeCover($validated['cover']);
        $authorIds = $validated['authors'];
        $attributes = collect($validated)->except(['cover', 'authors'])->all();

        $book = DB::transaction(function () use ($attributes, $authorIds, $coverPath) {
            $book = Book::create([...$attributes, 'cover' => $coverPath]);
            $book->authors()->sync($authorIds);

            return $book;
        });

        return to_route('admin.books.show', $book)->with('success', 'Buku berhasil ditambahkan.');
    }

    public function show(Book $book): View
    {
        $this->authorize('view', $book);

        $book->load(['authors', 'category', 'publisher', 'shelf']);
        $copies = $book->copies()->orderBy('id')->paginate(10);

        return view('books.show', [
            'book' => $book,
            'copies' => $copies,
            'coverUrl' => $this->coverUrl($book->cover),
        ]);
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        return view('books.edit', $this->formData($book));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $newCover = isset($validated['cover']) ? $this->storeCover($validated['cover']) : null;
        $previousCover = $book->cover;
        $authorIds = $validated['authors'];
        $previousAuthorIds = $book->authors()->orderBy('authors.id')->pluck('authors.id')->map(fn ($id): int => (int) $id)->all();
        $newAuthorIds = array_map('intval', $authorIds);
        $authorsChanged = $previousAuthorIds !== collect($newAuthorIds)->sort()->values()->all();
        $attributes = collect($validated)->except(['cover', 'authors'])->all();

        if ($newCover !== null) {
            $attributes['cover'] = $newCover;
        }

        DB::transaction(function () use ($book, $attributes, $newAuthorIds, $previousAuthorIds, $authorsChanged) {
            $book->update($attributes);
            $book->authors()->sync($newAuthorIds);

            if ($authorsChanged) {
                ActivityLog::query()->create([
                    'user_id' => Auth::id(),
                    'subject_type' => $book->getMorphClass(),
                    'subject_id' => $book->getKey(),
                    'action' => 'book.authors_updated',
                    'description' => 'Book authors updated',
                    'properties' => [
                        'previous_author_ids' => $previousAuthorIds,
                        'new_author_ids' => collect($newAuthorIds)->sort()->values()->all(),
                    ],
                ]);
            }
        });

        if ($newCover !== null) {
            $this->deleteCover($previousCover);
        }

        return to_route('admin.books.show', $book)->with('success', 'Buku berhasil diperbarui.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        if ($book->copies()->exists() || $book->reservations()->exists()) {
            return back()->with('error', 'Buku yang memiliki eksemplar atau reservasi tidak dapat dihapus.');
        }

        $cover = $book->cover;
        DB::transaction(fn () => $book->delete());
        $this->deleteCover($cover);

        return to_route('admin.books.index')->with('success', 'Buku berhasil dihapus.');
    }

    private function formData(?Book $book = null): array
    {
        return [
            'book' => $book,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'authors' => Author::query()->orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::query()->orderBy('name')->get(['id', 'name']),
            'shelves' => Shelf::query()->orderBy('code')->get(['id', 'code', 'name']),
            'selectedAuthorIds' => $book?->authors()->pluck('authors.id')->all() ?? [],
            'coverUrl' => $book ? $this->coverUrl($book->cover) : null,
        ];
    }

    private function coverUrl(?string $path): ?string
    {
        $disk = Storage::disk('public');

        return $path && $disk->exists($path) ? $disk->url($path) : null;
    }

    private function storeCover(UploadedFile $file): string
    {
        $path = $file->store('covers', 'public');

        if (! is_string($path)) {
            throw new RuntimeException('Sampul buku gagal disimpan.');
        }

        return $path;
    }

    private function deleteCover(?string $path): void
    {
        $disk = Storage::disk('public');

        if ($path && $disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Sampul buku gagal dihapus dari penyimpanan.');
        }
    }
}
