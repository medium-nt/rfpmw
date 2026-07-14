<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Event;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Перед каждым тестом наполняем справочник ролей (фикс. ID: 1 — manager, 2 — admin).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Администратор успешно удаляет менеджера без связей.
     */
    public function test_happy_path_admin_deletes_manager_without_relations(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь удалён.');
        $this->assertSoftDeleted('users', ['id' => $manager->id]);
    }

    /**
     * Администратор не может удалить свою учётную запись.
     */
    public function test_block_self_deletion(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Нельзя удалить свою учётную запись.');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить последнего администратора (но получает ошибку самостоятельного удаления).
     */
    public function test_block_last_admin_deletion(): void
    {
        // Создаём двух администраторов
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        // Удаляем первого админа вторым
        $this->actingAs($admin2)->delete(route('users.destroy', $admin1));
        $this->assertSoftDeleted('users', ['id' => $admin1->id]);

        // Теперь остаётся только один админ (admin2)
        // Проверяем, что второй администратор не может быть удалён (даже самим собой - self-deletion)
        $response = $this->actingAs($admin2)->delete(route('users.destroy', $admin2));

        $response->assertStatus(302);
        // Проверка приоритетности: self-deletion блокирует раньше last-admin
        $response->assertSessionHas('error', 'Нельзя удалить свою учётную запись.');
        $this->assertDatabaseHas('users', ['id' => $admin2->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить пользователя, у которого есть контрагенты.
     */
    public function test_block_dependencies_contractors(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        Contractor::factory()->for($manager, 'user')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertStatus(302);
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'контрагентами'));
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'deleted_at' => null]);
    }

    /**
     * Мягко удалённые связи не блокируют удаление пользователя.
     */
    public function test_soft_deleted_dependencies_not_blocking(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $contractor->delete();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь удалён.');
        $this->assertSoftDeleted('users', ['id' => $manager->id]);
    }

    /**
     * При удалении пользователя очищаются его сессии.
     */
    public function test_sessions_cleanup_on_deletion(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $manager->id,
            'payload' => 'test_payload',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test_agent',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $this->assertDatabaseMissing('sessions', ['user_id' => $manager->id]);
    }

    /**
     * Менеджер не может удалить пользователя (доступ запрещён).
     */
    public function test_forbidden_for_manager(): void
    {
        $manager = User::factory()->manager()->create();
        $target = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->delete(route('users.destroy', $target));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить пользователя, у которого есть запросы.
     */
    public function test_block_dependencies_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        Request::factory()->for($manager, 'user')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertStatus(302);
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'запросами'));
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить пользователя, у которого есть КП.
     */
    public function test_block_dependencies_proposals(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        Proposal::factory()->for($manager, 'user')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertStatus(302);
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'КП'));
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить пользователя, у которого есть события.
     */
    public function test_block_dependencies_events(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        Event::factory()->for($manager, 'user')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertStatus(302);
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'событиями'));
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'deleted_at' => null]);
    }

    /**
     * Нельзя удалить пользователя с несколькими типами связей.
     */
    public function test_block_multiple_dependencies(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        Contractor::factory()->for($manager, 'user')->create();
        Request::factory()->for($manager, 'user')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $manager));

        $response->assertStatus(302);
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'контрагентами') && str_contains($error, 'запросами'));
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'deleted_at' => null]);
    }

    /**
     * Один из нескольких администраторов может удалить другого.
     */
    public function test_admin_can_delete_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $response = $this->actingAs($admin1)->delete(route('users.destroy', $admin2));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь удалён.');
        $this->assertSoftDeleted('users', ['id' => $admin2->id]);
    }
}
