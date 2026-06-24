<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Контрагент создаётся через factory с привязкой к менеджеру.
     */
    public function test_can_create_contractor(): void
    {
        $contractor = Contractor::factory()->create();

        $this->assertDatabaseHas('contractors', ['id' => $contractor->id]);
        $this->assertInstanceOf(User::class, $contractor->user);
    }

    /**
     * ИНН контрагента уникален.
     */
    public function test_inn_is_unique(): void
    {
        Contractor::factory()->create(['inn' => '123456789012']);

        $this->expectException(QueryException::class);

        Contractor::factory()->create(['inn' => '123456789012']);
    }

    /**
     * Контрагент поддерживает мягкое удаление.
     */
    public function test_contractor_can_be_soft_deleted(): void
    {
        $contractor = Contractor::factory()->create();

        $contractor->delete();

        $this->assertSoftDeleted('contractors', ['id' => $contractor->id]);
        $this->assertNull(Contractor::find($contractor->id));
        $this->assertNotNull(Contractor::withTrashed()->find($contractor->id));
    }

    /**
     * При удалении менеджера user_id контрагента обнуляется (SET NULL).
     */
    public function test_user_id_becomes_null_on_user_delete(): void
    {
        $contractor = Contractor::factory()->create();
        $user = $contractor->user;

        $user->forceDelete();

        $this->assertNull($contractor->fresh()->user_id);
    }
}
