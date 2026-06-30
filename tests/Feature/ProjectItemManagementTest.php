<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectItemManagementTest extends TestCase
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
     * Админ может создать позицию проекта с валидными данными.
     */
    public function test_admin_can_store_project_item(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('project-items.store', $project), [
                'item_id' => $item->id,
                'quantity' => 10,
                'price' => 150.50,
                'status' => 'planned',
                'production_start_date' => '2026-02-01',
            ])
            ->assertRedirect(route('projects.show', $project));

        $projectItem = ProjectItem::where('project_id', $project->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($projectItem);
        $this->assertSame(10, $projectItem->quantity);
        $this->assertSame(150.50, (float) $projectItem->price);
        $this->assertSame('planned', $projectItem->status);
        $this->assertSame('2026-02-01', $projectItem->production_start_date->format('Y-m-d'));

        // Проверка пересчёта usd_value проекта
        $project->refresh();
        $this->assertSame(1505.00, (float) $project->usd_value); // 10 * 150.50
    }

    /**
     * Админ может обновить позицию проекта.
     */
    public function test_admin_can_update_project_item(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create([
            'quantity' => 5,
            'price' => 100.00,
            'status' => 'planned',
        ]);
        $originalItemId = $projectItem->item_id;
        $anotherItem = Item::factory()->create();

        $this->actingAs($admin)
            ->put(route('project-items.update', [$project, $projectItem]), [
                'item_id' => $anotherItem->id, // Попытка сменить артикул
                'quantity' => 20,
                'price' => 200.75,
                'status' => 'in_production',
                'production_start_date' => '2026-03-01',
            ])
            ->assertRedirect(route('projects.show', $project));

        // item_id НЕ должен измениться (не валидируется в UpdateProjectItemRequest)
        $projectItem->refresh();
        $this->assertSame($originalItemId, $projectItem->item_id);
        $this->assertSame(20, $projectItem->quantity);
        $this->assertSame(200.75, (float) $projectItem->price);
        $this->assertSame('in_production', $projectItem->status);
        $this->assertSame('2026-03-01', $projectItem->production_start_date->format('Y-m-d'));

        // Проверка пересчёта usd_value проекта
        $project->refresh();
        $this->assertSame(4015.00, (float) $project->usd_value); // 20 * 200.75
    }

    /**
     * Админ может удалить позицию проекта.
     */
    public function test_admin_can_destroy_project_item(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create([
            'quantity' => 3,
            'price' => 50.00,
        ]);

        $this->actingAs($admin)
            ->delete(route('project-items.destroy', [$project, $projectItem]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('project_item', [
            'id' => $projectItem->id,
        ]);

        // Проверка пересчёта usd_value проекта (должен стать 0)
        $project->refresh();
        $this->assertSame(0.0, (float) $project->usd_value);
    }

    /**
     * Админ может открыть форму редактирования позиции.
     */
    public function test_admin_can_edit_project_item(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($admin)
            ->get(route('project-items.edit', [$project, $projectItem]))
            ->assertOk();
    }

    /**
     * Валидация: item_id обязателен при создании.
     */
    public function test_item_id_is_required_on_store(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('project-items.store', $project), [
                'quantity' => 10,
                'price' => 100.00,
            ])
            ->assertSessionHasErrors(['item_id']);
    }

    /**
     * Валидация: item_id должен существовать в таблице items.
     */
    public function test_item_id_must_exist(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('project-items.store', $project), [
                'item_id' => 99999,
                'quantity' => 10,
            ])
            ->assertSessionHasErrors(['item_id']);
    }

    /**
     * Валидация: status должен быть из справочника.
     */
    public function test_status_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('project-items.store', $project), [
                'item_id' => $item->id,
                'status' => 'invalid_status',
            ])
            ->assertSessionHasErrors(['status']);
    }

    /**
     * Валидация: production_start_date должна быть валидной датой.
     */
    public function test_production_start_date_must_be_valid_date(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('project-items.store', $project), [
                'item_id' => $item->id,
                'production_start_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['production_start_date']);
    }

    /**
     * Менеджер-владелец проекта может создавать позиции.
     */
    public function test_manager_owner_can_store_project_item(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('project-items.store', $project), [
                'item_id' => $item->id,
                'quantity' => 5,
                'price' => 99.99,
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_item', [
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Менеджер-владелец проекта может обновлять позиции.
     */
    public function test_manager_owner_can_update_project_item(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->put(route('project-items.update', [$project, $projectItem]), [
                'quantity' => 15,
                'price' => 175.25,
            ])
            ->assertRedirect(route('projects.show', $project));

        $projectItem->refresh();
        $this->assertSame(15, $projectItem->quantity);
        $this->assertSame(175.25, (float) $projectItem->price);
    }

    /**
     * Менеджер-владелец проекта может удалять позиции.
     */
    public function test_manager_owner_can_destroy_project_item(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->delete(route('project-items.destroy', [$project, $projectItem]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('project_item', [
            'id' => $projectItem->id,
        ]);
    }

    /**
     * Менеджер-владелец проекта может редактировать позиции.
     */
    public function test_manager_owner_can_edit_project_item(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->get(route('project-items.edit', [$project, $projectItem]))
            ->assertOk();
    }

    /**
     * Менеджер не может создавать позиции в чужом проекте (403).
     */
    public function test_manager_cannot_store_item_in_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($otherManager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('project-items.store', $project), [
                'item_id' => $item->id,
                'quantity' => 10,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может обновлять позиции в чужом проекте (403).
     */
    public function test_manager_cannot_update_item_in_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($otherManager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->put(route('project-items.update', [$project, $projectItem]), [
                'quantity' => 20,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может удалять позиции в чужом проекте (403).
     */
    public function test_manager_cannot_destroy_item_in_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($otherManager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->delete(route('project-items.destroy', [$project, $projectItem]))
            ->assertForbidden();
    }

    /**
     * Менеджер не может редактировать позиции в чужом проекте (403).
     */
    public function test_manager_cannot_edit_item_in_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($otherManager, 'user')->create());
        $projectItem = ProjectItem::factory()->for($project)->create();

        $this->actingAs($manager)
            ->get(route('project-items.edit', [$project, $projectItem]))
            ->assertForbidden();
    }

    /**
     * Чужая позиция через URL другого проекта возвращает 404.
     */
    public function test_accessing_item_from_different_project_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $projectA = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $projectB = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $projectItemA = ProjectItem::factory()->for($projectA)->create();

        // Попытка редактировать позицию проекта A через URL проекта B
        $this->actingAs($admin)
            ->get(route('project-items.edit', [$projectB, $projectItemA]))
            ->assertNotFound();
    }

    /**
     * usd_value корректно пересчитывается для нескольких позиций.
     */
    public function test_usd_value_is_correctly_calculated_for_multiple_items(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        // Создаём пустой проект через Project::create() (без фабрики с её случайными позициями)
        $project = Project::create([
            'contractor_id' => $contractor->id,
            'name' => 'Test Project',
            'date' => now(),
            'status' => 'new',
        ]);

        // Создаём две позиции с конкретными значениями
        ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 5,
            'price' => 100.00,
            'status' => 'planned',
        ]);

        ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 3,
            'price' => 50.00,
            'status' => 'planned',
        ]);

        // Пересчитываем usd_value (как это делает контроллер)
        $project->recalcUsdValue();

        $expectedUsdValue = (5 * 100.00) + (3 * 50.00); // 500 + 150 = 650
        $this->assertSame($expectedUsdValue, (float) $project->usd_value);
    }

    /**
     * Создаёт проект для заданного контрагента.
     */
    private function projectFor(Contractor $contractor): Project
    {
        return Project::factory()->create(['contractor_id' => $contractor->id]);
    }
}
