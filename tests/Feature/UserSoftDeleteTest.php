<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Пользователь поддерживает мягкое удаление.
     */
    public function test_user_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Мягко удалённый пользователь не попадает в выборку по умолчанию.
     */
    public function test_soft_deleted_user_excluded_from_default_query(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertFalse(User::where('id', $user->id)->exists());
        $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());
    }
}
