<?php

namespace App\Providers;

use App\Models\User;
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
        Gate::define('view-admin-dashboard', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-books', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-circulation', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-members', fn (User $user): bool => $user->hasAnyRole(['admin', 'pustakawan']));
        Gate::define('manage-settings', fn (User $user): bool => $user->hasRole('admin'));
        Gate::define('view-member-area', fn (User $user): bool => $user->hasRole('anggota'));
    }
}
