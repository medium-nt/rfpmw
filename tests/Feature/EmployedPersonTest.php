<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployedPersonTest extends TestCase
{
    use RefreshDatabase;

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
}
