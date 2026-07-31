<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
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
