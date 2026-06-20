<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что менеджер получает 403 на странице списка пользователей.
     */
    public function test_manager_forbidden_on_index(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
    }

    /**
     * Проверяет, что менеджер получает 403 на форме создания.
     */
    public function test_manager_forbidden_on_create(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('users.create'))->assertForbidden();
    }

    /**
     * Проверяет, что менеджер получает 403 при сохранении нового пользователя.
     */
    public function test_manager_forbidden_on_store(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Юзер',
            'email' => 'x@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 1,
        ])->assertForbidden();
    }

    /**
     * Проверяет, что менеджер получает 403 на форме редактирования.
     */
    public function test_manager_forbidden_on_edit(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();

        $this->actingAs($manager)->get(route('users.edit', $user))->assertForbidden();
    }

    /**
     * Проверяет, что менеджер получает 403 при обновлении пользователя.
     */
    public function test_manager_forbidden_on_update(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();

        $this->actingAs($manager)->put(route('users.update', $user), [
            'name' => 'Имя',
            'email' => $user->email,
        ])->assertForbidden();
    }

    /**
     * Проверяет, что менеджер получает 403 при удалении пользователя.
     */
    public function test_manager_forbidden_on_destroy(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();

        $this->actingAs($manager)->delete(route('users.destroy', $user))->assertForbidden();
    }
}
