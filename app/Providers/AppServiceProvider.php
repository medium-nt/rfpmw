<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        // Пагинация в стиле Bootstrap 5 (согласована с AdminLTE).
        Paginator::useBootstrapFive();

        // Администратор автоматически проходит любую проверку Gate.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // Доступ только для администратора.
        Gate::define('is-admin', fn (User $user) => $user->isAdmin());

        // Доступ только для менеджера.
        Gate::define('is-manager', fn (User $user) => $user->isManager());
    }
}
