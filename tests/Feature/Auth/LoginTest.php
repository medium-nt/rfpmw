<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что пользователь может войти с правильным логином и паролем.
     */
    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Проверяет, что пользователь не может войти с неправильным паролем.
     */
    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Проверяет, что пользователь не может войти с несуществующим логином.
     */
    public function test_user_cannot_login_with_unknown_username(): void
    {
        $response = $this->post('/login', [
            'username' => 'nonexistent',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
