<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что админ видит список пользователей.
     */
    public function test_admin_can_view_users_list(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create(['username' => 'other_user']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee($other->username);
    }

    /**
     * Проверяет, что админ видит форму создания пользователя.
     */
    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertSee('Создание пользователя');
    }

    /**
     * Проверяет, что админ видит форму редактирования пользователя.
     */
    public function test_admin_can_view_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Редактируемый Юзер']);

        $response = $this->actingAs($admin)->get(route('users.edit', $user));

        $response->assertStatus(200);
        $response->assertSee('Редактируемый Юзер');
    }

    /**
     * Проверяет, что админ может создать пользователя с выбранной ролью.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Новый Юзер',
            'email' => 'new@example.com',
            'username' => 'new_user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'username' => 'new_user',
            'name' => 'Новый Юзер',
            'role_id' => 1,
        ]);
    }

    /**
     * Проверяет валидацию обязательных полей при создании.
     */
    public function test_create_requires_all_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), []);

        $response->assertSessionHasErrors(['name', 'username', 'password', 'role_id']);
    }

    /**
     * Проверяет уникальность email при создании.
     */
    public function test_create_requires_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Юзер',
            'email' => 'taken@example.com',
            'username' => 'unique_login',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 1,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Проверяет, что админ может обновить имя и email пользователя.
     */
    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'before@example.com']);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Новое Имя',
            'email' => 'after@example.com',
            'username' => $user->username,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Новое Имя',
            'email' => 'after@example.com',
        ]);
    }

    /**
     * Проверяет, что при обновлении можно оставить свой текущий email.
     */
    public function test_update_allows_keeping_own_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => 'keep@example.com',
            'username' => $user->username,
        ]);

        $response->assertSessionHasNoErrors();
    }

    /**
     * Проверяет, что роль пользователя нельзя изменить при обновлении.
     */
    public function test_update_does_not_change_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->manager()->create(); // role_id = 1 (менеджер)

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'role_id' => 2, // попытка сделать админом — должна быть проигнорирована
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => 1,
        ]);
    }

    /**
     * Проверяет, что пустой пароль при обновлении не меняет хеш.
     */
    public function test_update_empty_password_keeps_hash(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Имя Без Пароля',
            'email' => $user->email,
            'username' => $user->username,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $user->refresh();
        $this->assertSame($originalHash, $user->password);
        $this->assertSame('Имя Без Пароля', $user->name);
    }

    /**
     * Проверяет, что заглушка удаления не удаляет пользователя и возвращает сообщение об ошибке.
     */
    public function test_destroy_returns_stub_error(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHas('error', 'Удаление пока недоступно.');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
