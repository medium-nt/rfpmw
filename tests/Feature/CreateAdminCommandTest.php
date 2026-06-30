<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что команда создаёт пользователя с ролью администратора.
     */
    public function test_creates_admin_with_admin_role(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Имя администратора', 'Иван Администраторов')
            ->expectsQuestion('Логин', 'admin')
            ->expectsQuestion('Пароль', 'secret123')
            ->assertSuccessful();

        $user = User::where('username', 'admin')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isAdmin());
    }

    /**
     * Проверяет, что команда завершается с ошибкой, если роль администратора отсутствует.
     */
    public function test_fails_when_admin_role_missing(): void
    {
        Role::query()->delete();

        $this->artisan('app:create-admin')
            ->expectsQuestion('Имя администратора', 'Иван Администраторов')
            ->expectsQuestion('Логин', 'admin')
            ->expectsQuestion('Пароль', 'secret123')
            ->assertFailed();
    }
}
