<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalCrudTest extends TestCase
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
     * Админ видит все КП.
     */
    public function test_admin_can_index_all_proposals(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->manager()->create();

        $this->makeProposalWith($admin, 'Иван Свой');
        $this->makeProposalWith($other, 'Пётр Чужой');

        $this->actingAs($admin)->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertSee('Пётр Чужой');
    }

    /**
     * Менеджер видит только КП своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_proposals(): void
    {
        $manager = User::factory()->manager()->create();
        $other = User::factory()->manager()->create();

        $this->makeProposalWith($manager, 'Иван Свой');
        $this->makeProposalWith($other, 'Пётр Чужой');

        $this->actingAs($manager)->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertDontSee('Пётр Чужой');
    }

    /**
     * Менеджер может открыть карточку своего КП.
     */
    public function test_manager_can_show_own_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('proposals.show', $proposal))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр чужого КП (403).
     */
    public function test_manager_cannot_show_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('proposals.show', $proposal))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму и обновить своё КП.
     */
    public function test_manager_can_edit_and_update_own_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('proposals.edit', $proposal))->assertOk();

        $this->actingAs($manager)->put(route('proposals.update', $proposal), [
            'employed_person_id' => $proposal->employed_person_id,
            'date' => '2026-04-15',
            'status' => 'sent',
        ])->assertRedirect(route('proposals.show', $proposal));

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'status' => 'sent']);
        $this->assertSame('2026-04-15 00:00:00', $proposal->fresh()->date->format('Y-m-d H:i:s'));
    }

    /**
     * Менеджеру запрещено редактировать чужое КП (403).
     */
    public function test_manager_cannot_edit_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('proposals.edit', $proposal))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму создания КП для своего контрагента.
     */
    public function test_manager_can_open_create_form_for_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->get(route('proposals.create', $contractor))
            ->assertOk()
            ->assertSee('Новое коммерческое предложение');
    }

    /**
     * Менеджеру запрещено создавать КП для чужого контрагента (403).
     */
    public function test_manager_cannot_open_create_form_for_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)
            ->get(route('proposals.create', $contractor))
            ->assertForbidden();
    }

    /**
     * Создание КП фиксирует текущего пользователя и оставляет usd_value = 0.
     */
    public function test_store_creates_proposal_with_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-04-01',
                'status' => 'draft',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $record = Proposal::latest('id')->first();
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
            ->post(route('proposals.store', $contractor), [])
            ->assertSessionHasErrors(['employed_person_id', 'date']);
    }

    /**
     * Нельзя привязать КП к сотруднику чужого контрагента.
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
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $otherEmployed->id,
                'date' => '2026-04-01',
            ])
            ->assertSessionHasErrors('employed_person_id');
    }

    /**
     * Удаление КП выполняет мягкое удаление и редиректит на список.
     */
    public function test_destroy_soft_deletes_proposal(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->delete(route('proposals.destroy', $proposal))
            ->assertRedirect(route('proposals.index'));

        $this->assertSoftDeleted('proposals', ['id' => $proposal->id]);
    }

    /**
     * Создание КП с project_id проекта того же контрагента успешно сохраняет связь.
     */
    public function test_store_proposal_with_same_contractor_project_succeeds(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $project = Project::factory()->for($contractor, 'contractor')->create();

        $this->actingAs($admin)
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $employed->id,
                'project_id' => $project->id,
                'date' => '2026-04-01',
                'status' => 'draft',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $proposal = Proposal::latest('id')->first();
        $this->assertNotNull($proposal);
        $this->assertSame($project->id, $proposal->project_id);
    }

    /**
     * Создание КП с project_id проекта другого контрагента вызывает ошибку валидации.
     */
    public function test_store_proposal_with_other_contractor_project_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $otherContractor = Contractor::factory()->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $otherProject = Project::factory()->for($otherContractor, 'contractor')->create();

        $this->actingAs($admin)
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $employed->id,
                'project_id' => $otherProject->id,
                'date' => '2026-04-01',
            ])
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseMissing('proposals', ['project_id' => $otherProject->id]);
    }

    /**
     * Создание КП без project_id (null) успешно сохраняет КП без привязки к проекту.
     */
    public function test_store_proposal_without_project_id_succeeds(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-04-01',
                'status' => 'draft',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $proposal = Proposal::latest('id')->first();
        $this->assertNotNull($proposal);
        $this->assertNull($proposal->project_id);
    }

    /**
     * Обновление КП позволяет установить project_id проекта того же контрагента.
     */
    public function test_update_proposal_can_set_project_id(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $proposal = $this->proposalFor($contractor);
        $project = Project::factory()->for($contractor, 'contractor')->create();

        $this->actingAs($admin)
            ->put(route('proposals.update', $proposal), [
                'employed_person_id' => $proposal->employed_person_id,
                'project_id' => $project->id,
                'date' => '2026-04-15',
                'status' => 'sent',
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertSame($project->id, $proposal->fresh()->project_id);
    }

    /**
     * Обновление КП с project_id чужого проекта вызывает ошибку валидации.
     */
    public function test_update_proposal_with_other_contractor_project_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $proposal = $this->proposalFor($contractor);
        $otherContractor = Contractor::factory()->create();
        $otherProject = Project::factory()->for($otherContractor, 'contractor')->create();

        $this->actingAs($admin)
            ->put(route('proposals.update', $proposal), [
                'employed_person_id' => $proposal->employed_person_id,
                'project_id' => $otherProject->id,
                'date' => '2026-04-15',
            ])
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseMissing('proposals', [
            'id' => $proposal->id,
            'project_id' => $otherProject->id,
        ]);
    }

    /**
     * Обновление КП позволяет убрать привязку к проекту (project_id = null).
     */
    public function test_update_proposal_can_remove_project_id(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $proposal = Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin)
            ->put(route('proposals.update', $proposal), [
                'employed_person_id' => $proposal->employed_person_id,
                'project_id' => null,
                'date' => '2026-04-15',
                'status' => 'sent',
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertNull($proposal->fresh()->project_id);
    }

    /**
     * Карточка КП eager-loads проект для отображения.
     */
    public function test_show_proposal_loads_project_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create(['name' => 'Тестовый Проект']);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $proposal = Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin)
            ->get(route('proposals.show', $proposal))
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
        $proposal = $this->proposalFor($contractor);

        $this->actingAs($admin)
            ->get(route('proposals.edit', $proposal))
            ->assertOk()
            ->assertSee('Живой проект')
            ->assertDontSee('Удалённый проект');
    }

    /**
     * Создаёт КП для заданного контрагента с сотрудником и автором, возвращая свежую модель.
     */
    private function proposalFor(Contractor $contractor): Proposal
    {
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        return Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => User::factory()->manager()->create()->id,
        ]);
    }

    /**
     * Создаёт контрагента для пользователя и КП его сотруднику с помеченным именем контрагента (маркер для scoping-проверок в index).
     */
    private function makeProposalWith(User $user, string $contractorName): void
    {
        $contractor = Contractor::factory()->for($user, 'user')->create(['name' => $contractorName]);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $user->id,
        ]);
    }
}
