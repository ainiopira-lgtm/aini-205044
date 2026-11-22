<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->hasRole('anggota') && ! $user->hasAnyRole(['admin', 'pustakawan'])) {
            return to_route('member.dashboard');
        }

        $books = Book::query()
            ->with(['authors', 'category'])
            ->withCount(['copies as available_copies_count' => fn ($query) => $query->where('status', 'available')])
            ->orderBy('title')
            ->paginate(8);

        return view('dashboard', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
            'categoryCount' => Category::query()->count(),
            'copyCount' => BookCopy::query()->count(),
        ]);
    }
}
