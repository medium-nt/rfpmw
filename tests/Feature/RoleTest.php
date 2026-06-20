<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет связь пользователя с ролью.
     */
    public function test_user_belongs_to_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertInstanceOf(Role::class, $admin->role);
        $this->assertSame('admin', $admin->role->slug);
    }

    /**
     * Проверяет метод isAdmin() для администратора и менеджера.
     */
    public function test_is_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($manager->isAdmin());
    }

    /**
     * Проверяет метод isManager() для администратора и менеджера.
     */
    public function test_is_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->isManager());
        $this->assertFalse($admin->isManager());
    }

    /**
     * Проверяет метод hasRole() по slug роли.
     */
    public function test_has_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('manager'));
    }

    /**
     * Проверяет, что новый пользователь по умолчанию получает роль менеджера (id=1).
     */
    public function test_default_role_is_manager(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isManager());
        $this->assertSame(1, $user->role_id);
    }
}
