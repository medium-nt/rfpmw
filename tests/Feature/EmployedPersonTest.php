<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployedPersonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Перед HTTP-тестами наполняем справочник ролей (фикс. ID: 1 — manager, 2 — admin).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Связка человек ↔ компания уникальна.
     */
    public function test_pair_person_contractor_is_unique(): void
    {
        $person = ContactPerson::factory()->create();
        $contractor = Contractor::factory()->create();

        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->expectException(QueryException::class);

        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);
    }

    /**
     * Жёсткое удаление человека каскадно удаляет его связки с компаниями.
     */
    public function test_cascade_delete_on_contact_person_force_delete(): void
    {
        $employed = EmployedPerson::factory()->create();

        $employed->contactPerson->forceDelete();

        $this->assertDatabaseMissing('employed_people', ['id' => $employed->id]);
    }

    /**
     * Жёсткое удаление контрагента каскадно удаляет его сотрудников.
     */
    public function test_cascade_delete_on_contractor_force_delete(): void
    {
        $employed = EmployedPerson::factory()->create();

        $employed->contractor->forceDelete();

        $this->assertDatabaseMissing('employed_people', ['id' => $employed->id]);
    }

    /**
     * Менеджер может привязать существующего человека к своему контрагенту.
     */
    public function test_manager_can_attach_existing_person_to_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $person = ContactPerson::factory()->create();

        $this->actingAs($manager)
            ->post(route('employed-people.store', $contractor), [
                'contact_person_id' => $person->id,
                'position' => 'Директор',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $this->assertDatabaseHas('employed_people', [
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
            'position' => 'Директор',
            'deleted_at' => null,
        ]);
    }

    /**
     * Менеджеру запрещено привязывать людей к чужому контрагенту (403).
     */
    public function test_manager_cannot_attach_to_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();
        $person = ContactPerson::factory()->create();

        $this->actingAs($manager)
            ->post(route('employed-people.store', $contractor), [
                'contact_person_id' => $person->id,
            ])
            ->assertForbidden();
    }

    /**
     * Обновление должности существующей связи (вызывается из карточки человека).
     */
    public function test_manager_can_update_position(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
            'position' => 'Старая',
        ]);

        $this->actingAs($manager)
            ->put(route('employed-people.update', [$contractor, $employed]), ['position' => 'Новая'])
            ->assertRedirect(route('contact-people.show', $employed->contactPerson));

        $this->assertSame('Новая', $employed->fresh()->position);
    }

    /**
     * Отвязка удаляет связь (forceDelete), освобождая уникальную пару для повторной привязки.
     */
    public function test_manager_can_detach_person(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('employed-people.destroy', [$contractor, $employed]))
            ->assertRedirect(route('contractors.show', $contractor));

        $this->assertDatabaseMissing('employed_people', ['id' => $employed->id]);
    }

    /**
     * Повторная привязка уже активной пары отклоняется валидацией.
     */
    public function test_cannot_attach_duplicate_active_pair(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $person = ContactPerson::factory()->create();
        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($admin)
            ->post(route('employed-people.store', $contractor), ['contact_person_id' => $person->id])
            ->assertSessionHasErrors(['contact_person_id']);

        $this->assertSame(
            1,
            EmployedPerson::query()->where('contact_person_id', $person->id)->where('contractor_id', $contractor->id)->count()
        );
    }

    /**
     * Привязка ранее отвязанной (soft-deleted) пары восстанавливает связь с новой должностью.
     */
    public function test_can_restore_trashed_pair_on_attach(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $person = ContactPerson::factory()->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
            'position' => 'Старая',
        ]);
        $employed->delete();

        $this->actingAs($admin)
            ->post(route('employed-people.store', $contractor), [
                'contact_person_id' => $person->id,
                'position' => 'Восстановленная',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $this->assertNull($employed->fresh()->deleted_at);
        $this->assertSame('Восстановленная', $employed->fresh()->position);
    }

    /**
     * Связь, принадлежащая другому контрагенту, в чужом URL даёт 404 (даже для админа).
     */
    public function test_admin_gets_404_for_employed_person_of_other_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractorA = Contractor::factory()->for($admin, 'user')->create();
        $contractorB = Contractor::factory()->for($admin, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractorB->id,
        ]);

        $this->actingAs($admin)
            ->put(route('employed-people.update', [$contractorA, $employed]), ['position' => 'X'])
            ->assertNotFound();
    }
}
