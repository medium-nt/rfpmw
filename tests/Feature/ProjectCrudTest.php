<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
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
     * Админ видит все проекты.
     */
    public function test_admin_can_index_all_projects(): void
    {
        $admin = User::factory()->admin()->create();

        $own = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());
        $other = $this->projectFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($admin)->get(route('projects.index'))
            ->assertOk()
            ->assertSee($own->name)
            ->assertSee($other->name);
    }

    /**
     * Менеджер видит только проекты своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_projects(): void
    {
        $manager = User::factory()->manager()->create();

        $own = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());
        $other = $this->projectFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('projects.index'))
            ->assertOk()
            ->assertSee($own->name)
            ->assertDontSee($other->name);
    }

    /**
     * Менеджер может открыть карточку своего проекта.
     */
    public function test_manager_can_show_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('projects.show', $project))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр чужого проекта (403).
     */
    public function test_manager_cannot_show_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('projects.show', $project))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму и обновить свой проект.
     */
    public function test_manager_can_edit_and_update_own_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('projects.edit', $project))->assertOk();

        $this->actingAs($manager)->put(route('projects.update', $project), [
            'name' => 'Обновлённый проект',
            'date' => '2026-01-15',
            'status' => 'in_progress',
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Обновлённый проект', 'status' => 'in_progress']);
    }

    /**
     * Менеджеру запрещено редактировать чужой проект (403).
     */
    public function test_manager_cannot_edit_other_project(): void
    {
        $manager = User::factory()->manager()->create();
        $project = $this->projectFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('projects.edit', $project))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму создания проекта для своего контрагента.
     */
    public function test_manager_can_open_create_form_for_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->get(route('projects.create', $contractor))
            ->assertOk()
            ->assertSee('Новый проект');
    }

    /**
     * Менеджеру запрещено создавать проект для чужого контрагента (403).
     */
    public function test_manager_cannot_open_create_form_for_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)
            ->get(route('projects.create', $contractor))
            ->assertForbidden();
    }

    /**
     * Создание проекта админом привязывает его к контрагенту; usd_value остаётся 0 (не вводится вручную).
     */
    public function test_admin_can_store_new_project(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('projects.store', $contractor), [
                'name' => 'Сварка корпуса',
                'date' => '2026-02-01',
                'status' => 'new',
                'description' => 'Описание проекта',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $project = Project::where('name', 'Сварка корпуса')->first();
        $this->assertNotNull($project);
        $this->assertSame($contractor->id, $project->contractor_id);
        $this->assertSame(0.0, (float) $project->usd_value);
    }

    /**
     * Обязательные поля name и date проверяются.
     */
    public function test_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('projects.store', $contractor), [])
            ->assertSessionHasErrors(['name', 'date']);
    }

    /**
     * Нельзя назначить ответственным сотрудника чужого контрагента.
     */
    public function test_responsible_person_must_belong_to_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $otherEmployed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => Contractor::factory()->create()->id,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.store', $contractor), [
                'name' => 'Проект с чужим ответственным',
                'date' => '2026-02-01',
                'responsible_person_id' => $otherEmployed->id,
            ])
            ->assertSessionHasErrors('responsible_person_id');
    }

    /**
     * Удаление проекта выполняет мягкое удаление и редиректит на список.
     */
    public function test_destroy_soft_deletes_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /**
     * Создаёт проект для заданного контрагента, возвращая свежую модель.
     */
    private function projectFor(Contractor $contractor): Project
    {
        return Project::factory()->create(['contractor_id' => $contractor->id]);
    }
}
