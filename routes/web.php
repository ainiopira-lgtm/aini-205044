<?php

use App\Http\Controllers\ProfileController;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::get('/dashboard', function () {
    $books = Book::query()
        ->with(['authors', 'category'])
        ->withCount(['copies as available_copies_count' => fn ($query) => $query->where('status', 'available')])
        ->orderBy('title')
        ->paginate(8);

    return view('dashboard', [
        'books' => $books,
        'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
        'categoryCount' => Category::query()->count(),
        'copyCount' => \App\Models\BookCopy::query()->count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
