<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
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
     * Админ видит все события.
     */
    public function test_admin_can_index_all_events(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->manager()->create();

        $this->makeEventWith($admin, 'Иван Свой');
        $this->makeEventWith($other, 'Пётр Чужой');

        $this->actingAs($admin)->get(route('events.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertSee('Пётр Чужой');
    }

    /**
     * Менеджер видит только события своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_events(): void
    {
        $manager = User::factory()->manager()->create();
        $other = User::factory()->manager()->create();

        $this->makeEventWith($manager, 'Иван Свой');
        $this->makeEventWith($other, 'Пётр Чужой');

        $this->actingAs($manager)->get(route('events.index'))
            ->assertOk()
            ->assertSee('Иван Свой')
            ->assertDontSee('Пётр Чужой');
    }

    /**
     * Менеджер может открыть карточку своего события.
     */
    public function test_manager_can_show_own_event(): void
    {
        $manager = User::factory()->manager()->create();
        $event = $this->eventFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('events.show', $event))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр чужого события (403).
     */
    public function test_manager_cannot_show_other_event(): void
    {
        $manager = User::factory()->manager()->create();
        $event = $this->eventFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('events.show', $event))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму и обновить своё событие.
     */
    public function test_manager_can_edit_and_update_own_event(): void
    {
        $manager = User::factory()->manager()->create();
        $event = $this->eventFor(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('events.edit', $event))->assertOk();

        $this->actingAs($manager)->put(route('events.update', $event), [
            'employed_person_id' => $event->employed_person_id,
            'event_type' => 'meeting',
            'date' => '2026-05-15',
            'subject' => 'Встреча на объекте',
        ])->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_type' => 'meeting', 'subject' => 'Встреча на объекте']);
        $this->assertSame('2026-05-15 00:00:00', $event->fresh()->date->format('Y-m-d H:i:s'));
    }

    /**
     * Менеджеру запрещено редактировать чужое событие (403).
     */
    public function test_manager_cannot_edit_other_event(): void
    {
        $manager = User::factory()->manager()->create();
        $event = $this->eventFor(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('events.edit', $event))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму создания события для своего контрагента.
     */
    public function test_manager_can_open_create_form_for_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->get(route('events.create', $contractor))
            ->assertOk()
            ->assertSee('Новое событие');
    }

    /**
     * Менеджеру запрещено создавать событие для чужого контрагента (403).
     */
    public function test_manager_cannot_open_create_form_for_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)
            ->get(route('events.create', $contractor))
            ->assertForbidden();
    }

    /**
     * Создание события фиксирует текущего пользователя и обязательные поля.
     */
    public function test_store_creates_event_with_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('events.store', $contractor), [
                'employed_person_id' => $employed->id,
                'event_type' => 'call',
                'date' => '2026-05-01',
                'subject' => 'Первый звонок',
                'description' => 'Обсудили условия',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $record = Event::latest('id')->first();
        $this->assertNotNull($record);
        $this->assertSame($employed->id, $record->employed_person_id);
        $this->assertSame($admin->id, $record->user_id);
        $this->assertSame('call', $record->event_type);
    }

    /**
     * Обязательные поля проверяются.
     */
    public function test_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('events.store', $contractor), [])
            ->assertSessionHasErrors(['employed_person_id', 'event_type', 'date']);
    }

    /**
     * Нельзя создать событие для сотрудника чужого контрагента.
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
            ->post(route('events.store', $contractor), [
                'employed_person_id' => $otherEmployed->id,
                'event_type' => 'call',
                'date' => '2026-05-01',
            ])
            ->assertSessionHasErrors('employed_person_id');
    }

    /**
     * Событие можно привязать к проекту этого контрагента.
     */
    public function test_store_can_link_to_contractor_project(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $project = Project::factory()->create(['contractor_id' => $contractor->id]);

        $this->actingAs($admin)
            ->post(route('events.store', $contractor), [
                'employed_person_id' => $employed->id,
                'event_type' => 'meeting',
                'date' => '2026-05-01',
                'link' => "project:{$project->id}",
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $this->assertDatabaseHas('events', ['project_id' => $project->id]);
    }

    /**
     * Нельзя привязать событие к проекту чужого контрагента.
     */
    public function test_cannot_link_to_other_contractor_project(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $otherProject = Project::factory()->create();

        $this->actingAs($admin)
            ->post(route('events.store', $contractor), [
                'employed_person_id' => $employed->id,
                'event_type' => 'call',
                'date' => '2026-05-01',
                'link' => "project:{$otherProject->id}",
            ])
            ->assertSessionHasErrors('link');
    }

    /**
     * Удаление события выполняет мягкое удаление и редиректит на список.
     */
    public function test_destroy_soft_deletes_event(): void
    {
        $admin = User::factory()->admin()->create();
        $event = $this->eventFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    /**
     * Создаёт событие для заданного контрагента с сотрудником и автором, возвращая свежую модель.
     */
    private function eventFor(Contractor $contractor): Event
    {
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        return Event::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => User::factory()->manager()->create()->id,
        ]);
    }

    /**
     * Создаёт контрагента для пользователя и событие его сотруднику с помеченным ФИО (для проверок scoping).
     */
    private function makeEventWith(User $user, string $fio): void
    {
        $contractor = Contractor::factory()->for($user, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create(['fio' => $fio])->id,
            'contractor_id' => $contractor->id,
        ]);

        Event::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $user->id,
        ]);
    }
}
