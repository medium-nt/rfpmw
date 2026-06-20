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
        // Администратор автоматически проходит любую проверку Gate.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // Доступ только для администратора.
        Gate::define('is-admin', fn (User $user) => $user->isAdmin());

        // Доступ только для менеджера.
        Gate::define('is-manager', fn (User $user) => $user->isManager());
    }
}
