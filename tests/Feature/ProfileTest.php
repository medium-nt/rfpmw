<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что авторизованный пользователь видит свою страницу профиля.
     */
    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Александр']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Александр');
        $response->assertSee('Мой профиль');
    }

    /**
     * Проверяет, что менеджер (не админ) тоже имеет доступ к своему профилю.
     */
    public function test_manager_can_access_profile(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get(route('profile.edit'));

        $response->assertStatus(200);
    }

    /**
     * Проверяет, что гость перенаправляется на страницу входа.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Проверяет обновление имени и email текущего пользователя.
     */
    public function test_profile_update_changes_name_and_email(): void
    {
        $user = User::factory()->create(['email' => 'before@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Новое Имя',
            'email' => 'after@example.com',
            'username' => $user->username,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Новое Имя',
            'email' => 'after@example.com',
        ]);
    }

    /**
     * Проверяет, что пользователь может оставить свой текущий email.
     */
    public function test_profile_update_allows_keeping_own_email(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'keep@example.com',
            'username' => $user->username,
        ]);

        $response->assertSessionHasNoErrors();
    }

    /**
     * Проверяет, что чужой email вызвать ошибку уникальности.
     */
    public function test_profile_update_rejects_duplicate_email(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
            'username' => $user->username,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Проверяет успешную смену пароля при верном текущем пароле.
     */
    public function test_password_can_be_changed_with_correct_current(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame($originalHash, $user->password);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    /**
     * Проверяет, что смена пароля невозможна при неверном текущем пароле.
     */
    public function test_password_change_fails_with_wrong_current(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrongpass',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertSame($originalHash, $user->refresh()->password);
    }
}
