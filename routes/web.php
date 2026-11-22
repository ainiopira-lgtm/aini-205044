<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CirculationController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShelfController;
use App\Models\Book;
use App\Models\BookCopy;
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
        'copyCount' => BookCopy::query()->count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin|pustakawan'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('authors', AuthorController::class);
        Route::resource('publishers', PublisherController::class);
        Route::resource('shelves', ShelfController::class);
        Route::resource('books', BookController::class);
        Route::get('members/{member}/card', [MemberController::class, 'card'])->name('members.card');
        Route::resource('members', MemberController::class);
        Route::get('circulation', [CirculationController::class, 'index'])->name('circulation.index');
        Route::post('circulation/members', [CirculationController::class, 'selectMember'])->name('circulation.members.select');
        Route::post('circulation/copies', [CirculationController::class, 'addCopy'])->name('circulation.copies.add');
        Route::delete('circulation/copies', [CirculationController::class, 'removeCopy'])->name('circulation.copies.remove');
        Route::delete('circulation/draft', [CirculationController::class, 'clearDraft'])->name('circulation.draft.clear');
        Route::post('circulation/loans', [CirculationController::class, 'checkout'])->name('circulation.loans.store');
        Route::post('circulation/returns', [CirculationController::class, 'returnCopy'])->name('circulation.returns.store');
        Route::post('circulation/renewals', [CirculationController::class, 'renew'])->name('circulation.renewals.store');
        Route::get('fines', [FineController::class, 'index'])->name('fines.index');
        Route::post('fines/{fine}/pay', [FineController::class, 'pay'])->name('fines.pay');
        Route::get('fines/{fine}/receipt', [FineController::class, 'receipt'])->name('fines.receipt');

        Route::post('books/{book}/copies', [BookCopyController::class, 'store'])->name('books.copies.store');
        Route::patch('books/{book}/copies/{copy}', [BookCopyController::class, 'update'])->name('books.copies.update');
        Route::delete('books/{book}/copies/{copy}', [BookCopyController::class, 'destroy'])->name('books.copies.destroy');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
