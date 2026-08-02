<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Project;
use App\Models\Request;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCrudTest extends TestCase
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
     * Админ видит все запросы.
     */
    public function test_admin_can_index_all_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->manager()->create();

        $this->makeRequestWith($admin, 'Иван Свой');
        $this->makeRequestWith($other, 'Пётр Чужой');

        $this->actingAs($admin)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertSee('Пётр Чужой');
    }

    /**
     * Менеджер видит только запросы своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_requests(): void
    {
        $manager = User::factory()->manager()->create();
        $other = User::factory()->manager()->create();

        $this->makeRequestWith($manager, 'Иван Свой');
        $this->makeRequestWith($other, 'Пётр Чужой');

        $this->actingAs($manager)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertDontSee('Пётр Чужой');
    }

    /**
     * Менеджер может открыть карточку своего запроса.
     */
    public function test_manager_can_show_own_request(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('requests.show', $request))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр чужого запроса (403).
     */
    public function test_manager_cannot_show_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('requests.show', $request))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму и обновить свой запрос.
     */
    public function test_manager_can_edit_and_update_own_request(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('requests.edit', $request))->assertOk();

        $this->actingAs($manager)->put(route('requests.update', $request), [
            'employed_person_id' => $request->employed_person_id,
            'date' => '2026-03-15',
            'status' => 'waiting_reply',
        ])->assertRedirect(route('requests.show', $request));

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'waiting_reply']);
        $this->assertSame('2026-03-15 00:00:00', $request->fresh()->date->format('Y-m-d H:i:s'));
    }

    /**
     * Менеджеру запрещено редактировать чужой запрос (403).
     */
    public function test_manager_cannot_edit_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('requests.edit', $request))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму создания запроса для своего контрагента.
     */
    public function test_manager_can_open_create_form_for_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->get(route('requests.create', $contractor))
            ->assertOk()
            ->assertSee('Новый запрос');
    }

    /**
     * Менеджеру запрещено создавать запрос для чужого контрагента (403).
     */
    public function test_manager_cannot_open_create_form_for_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)
            ->get(route('requests.create', $contractor))
            ->assertForbidden();
    }

    /**
     * Создание запроса фиксирует текущего пользователя и оставляет usd_value = 0.
     */
    public function test_store_creates_request_with_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-03-01',
                'status' => 'new',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $record = Request::latest('id')->first();
        $this->assertNotNull($record);
        $this->assertSame($employed->id, $record->employed_person_id);
        $this->assertSame($admin->id, $record->user_id);
        $this->assertSame(0.0, (float) $record->usd_value);
    }

    /**
     * Обязательные поля проверяются.
     */
    public function test_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [])
            ->assertSessionHasErrors(['employed_person_id', 'date']);
    }

    /**
     * Нельзя привязать запрос к сотруднику чужого контрагента.
     */
    public function test_employed_person_must_belong_to_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $otherEmployed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => Contractor::factory()->create()->id,
        ]);

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $otherEmployed->id,
                'date' => '2026-03-01',
            ])
            ->assertSessionHasErrors('employed_person_id');
    }

    /**
     * Удаление запроса выполняет мягкое удаление и редиректит на список.
     */
    public function test_destroy_soft_deletes_request(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->delete(route('requests.destroy', $request))
            ->assertRedirect(route('requests.index'));

        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }

    /**
     * Создание запроса с project_id проекта того же контрагента сохраняет связь.
     */
    public function test_store_with_project_id_of_same_contractor_saves_relation(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-03-01',
                'status' => 'new',
                'project_id' => $project->id,
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $record = Request::latest('id')->first();
        $this->assertNotNull($record);
        $this->assertSame($project->id, $record->project_id);
        $this->assertDatabaseHas('requests', ['id' => $record->id, 'project_id' => $project->id]);
    }

    /**
     * Создание запроса без project_id (null) успешно сохраняет запрос без привязки к проекту.
     */
    public function test_store_without_project_id_saves_request_without_project(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-03-01',
                'status' => 'new',
                'project_id' => null,
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $record = Request::latest('id')->first();
        $this->assertNotNull($record);
        $this->assertNull($record->project_id);
        $this->assertDatabaseHas('requests', ['id' => $record->id, 'project_id' => null]);
    }

    /**
     * Создание запроса с project_id проекта другого контрагента возвращает ошибку валидации.
     */
    public function test_store_with_project_id_of_other_contractor_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $otherContractor = Contractor::factory()->for($admin, 'user')->create();
        $otherProject = Project::factory()->for($otherContractor, 'contractor')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-03-01',
                'status' => 'new',
                'project_id' => $otherProject->id,
            ])
            ->assertSessionHasErrors(['project_id']);

        $this->assertDatabaseMissing('requests', ['project_id' => $otherProject->id]);
    }

    /**
     * Обновление запроса с установкой project_id проекта того же контрагента сохраняет связь.
     */
    public function test_update_can_set_project_id_of_same_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();
        $request = $this->requestFor($contractor);

        $this->actingAs($admin)
            ->put(route('requests.update', $request), [
                'employed_person_id' => $request->employed_person_id,
                'date' => $request->date->format('Y-m-d'),
                'status' => $request->status,
                'project_id' => $project->id,
            ])
            ->assertRedirect(route('requests.show', $request));

        $this->assertSame($project->id, $request->fresh()->project_id);
        $this->assertDatabaseHas('requests', ['id' => $request->id, 'project_id' => $project->id]);
    }

    /**
     * Обновление запроса с project_id проекта другого контрагента возвращает ошибку валидации.
     */
    public function test_update_with_project_id_of_other_contractor_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $otherContractor = Contractor::factory()->for($admin, 'user')->create();
        $otherProject = Project::factory()->for($otherContractor, 'contractor')->create();
        $request = $this->requestFor($contractor);

        $this->actingAs($admin)
            ->put(route('requests.update', $request), [
                'employed_person_id' => $request->employed_person_id,
                'date' => $request->date->format('Y-m-d'),
                'status' => $request->status,
                'project_id' => $otherProject->id,
            ])
            ->assertSessionHasErrors(['project_id']);

        $this->assertDatabaseMissing('requests', ['id' => $request->id, 'project_id' => $otherProject->id]);
    }

    /**
     * Обновление запроса позволяет убрать привязку к проекту (project_id = null).
     */
    public function test_update_request_can_remove_project_id(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $request = Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin)
            ->put(route('requests.update', $request), [
                'employed_person_id' => $request->employed_person_id,
                'project_id' => null,
                'date' => '2026-03-15',
                'status' => 'waiting_reply',
            ])
            ->assertRedirect(route('requests.show', $request));

        $this->assertNull($request->fresh()->project_id);
    }

    /**
     * Карточка запроса eager-loads проект для отображения.
     */
    public function test_show_request_loads_project_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create(['name' => 'Тестовый Проект']);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $request = Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin)
            ->get(route('requests.show', $request))
            ->assertOk()
            ->assertSee('Тестовый Проект');
    }

    /**
     * Мягко-удалённый проект не предлагается в списке формы (SoftDeletes-скоуп Contractor::projects()).
     */
    public function test_edit_form_excludes_soft_deleted_project(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        Project::factory()->for($contractor, 'contractor')->create(['name' => 'Живой проект']);
        $deletedProject = Project::factory()->for($contractor, 'contractor')->create(['name' => 'Удалённый проект']);
        $deletedProject->delete();
        $request = $this->requestFor($contractor);

        $this->actingAs($admin)
            ->get(route('requests.edit', $request))
            ->assertOk()
            ->assertSee('Живой проект')
            ->assertDontSee('Удалённый проект');
    }

    /**
     * Создаёт запрос для заданного контрагента с сотрудником и автором, возвращая свежую модель.
     */
    private function requestFor(Contractor $contractor): Request
    {
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        return Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => User::factory()->manager()->create()->id,
        ]);
    }

    /**
     * Создаёт контрагента для пользователя и запрос от его сотрудника с помеченным именем контрагента (маркер для scoping-проверок в index).
     */
    private function makeRequestWith(User $user, string $contractorName): void
    {
        $contractor = Contractor::factory()->for($user, 'user')->create(['name' => $contractorName]);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $user->id,
        ]);
    }
}
