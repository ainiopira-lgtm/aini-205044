<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function home(): View
    {
        $books = Book::query()
            ->with(['authors', 'category'])
            ->withCount(['copies as available_copies_count' => fn ($query) => $query->where('status', 'available')])
            ->latest()
            ->limit(4)
            ->get();
        $this->addCoverUrls($books);

        return view('public.home', compact('books'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'author_id' => ['nullable', 'integer', 'exists:authors,id'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:'.(now()->year + 1)],
        ]);
        $search = trim($filters['q'] ?? '');

        $books = Book::query()
            ->with(['authors', 'category'])
            ->withCount(['copies as available_copies_count' => fn ($query) => $query->where('status', 'available')])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%")
                ->orWhereHas('authors', fn (Builder $authors) => $authors->where('name', 'like', "%{$search}%")))
            )
            ->when($filters['category_id'] ?? null, fn (Builder $query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['author_id'] ?? null, fn (Builder $query, $authorId) => $query->whereHas(
                'authors',
                fn (Builder $authors) => $authors->whereKey($authorId),
            ))
            ->when($filters['year'] ?? null, fn (Builder $query, $year) => $query->where('year', $year))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();
        $this->addCoverUrls($books->getCollection());

        return view('catalog.index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'authors' => Author::query()->orderBy('name')->pluck('name', 'id'),
            'years' => Book::query()->select('year')->distinct()->orderByDesc('year')->pluck('year'),
            'filters' => $filters,
            'search' => $search,
        ]);
    }

    public function show(Book $book): View
    {
        $book->load(['authors', 'category', 'publisher', 'shelf']);
        $book->loadCount([
            'copies',
            'copies as available_copies_count' => fn ($query) => $query->where('status', 'available'),
        ]);
        $borrowedCopiesCount = $book->copies()->where('status', 'borrowed')->count();
        $member = auth()->user()?->memberProfile;
        $hasActiveReservation = $member && Reservation::query()
            ->where('member_id', $member->id)
            ->where('book_id', $book->id)
            ->whereNull('fulfilled_at')
            ->where(fn ($query) => $query
                ->where('status', 'pending')
                ->orWhere(fn ($query) => $query
                    ->where('status', 'ready')
                    ->where('expires_at', '>', now())))
            ->exists();
        $canReserve = $member
            && $member->status === 'active'
            && $book->copies_count > 0
            && $borrowedCopiesCount === $book->copies_count
            && ! $hasActiveReservation;
        $coverUrl = $this->coverUrl($book->cover);

        return view('catalog.show', compact('book', 'borrowedCopiesCount', 'member', 'hasActiveReservation', 'canReserve', 'coverUrl'));
    }

    private function addCoverUrls(Collection $books): void
    {
        foreach ($books as $book) {
            $book->setAttribute('cover_url', $this->coverUrl($book->cover));
        }
    }

    private function coverUrl(?string $path): ?string
    {
        $disk = Storage::disk('public');

        return $path && $disk->exists($path) ? $disk->url($path) : null;
    }
}
