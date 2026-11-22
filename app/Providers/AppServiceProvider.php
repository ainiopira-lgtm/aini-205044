<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Shelf;
use App\Policies\AuthorPolicy;
use App\Policies\BookPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\PublisherPolicy;
use App\Policies\ShelfPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Author::class, AuthorPolicy::class);
        Gate::policy(Publisher::class, PublisherPolicy::class);
        Gate::policy(Shelf::class, ShelfPolicy::class);
        Gate::policy(Book::class, BookPolicy::class);

        Gate::define('view-admin-dashboard', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-books', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-circulation', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-members', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-settings', fn (User $user): bool => $user->hasRole('admin'));
        Gate::define('view-member-area', fn (User $user): bool => $user->hasRole('anggota'));
    }
}
