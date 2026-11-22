<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CirculationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShelfController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCatalogController::class, 'home'])->name('home');
Route::get('/catalog', [PublicCatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{book}', [PublicCatalogController::class, 'show'])->name('catalog.show');
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:anggota'])->group(function () {
    Route::get('/member/dashboard', [MemberPortalController::class, 'index'])->name('member.dashboard');
    Route::post('/member/loans/{loan}/renew', [MemberPortalController::class, 'renew'])->name('member.loans.renew');
    Route::post('/catalog/{book}/reservations', [ReservationController::class, 'store'])->name('catalog.reservations.store');
});

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
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/{format}', [ReportController::class, 'export'])
            ->whereIn('format', ['pdf', 'xlsx'])
            ->name('reports.export');
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
