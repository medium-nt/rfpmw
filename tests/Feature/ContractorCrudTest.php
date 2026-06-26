<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorCrudTest extends TestCase
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
     * Админ видит всех контрагентов.
     */
    public function test_admin_can_index_all_contractors(): void
    {
        $admin = User::factory()->admin()->create();

        $own = Contractor::factory()->for($admin, 'user')->create(['name' => 'ООО Админа']);
        $other = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create(['name' => 'ООО Чужое']);

        $this->actingAs($admin)->get(route('contractors.index'))
            ->assertOk()
            ->assertSee($own->name)
            ->assertSee($other->name);
    }

    /**
     * Менеджер видит только своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_contractors(): void
    {
        $manager = User::factory()->manager()->create();

        $own = Contractor::factory()->for($manager, 'user')->create(['name' => 'ООО Свой']);
        $other = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create(['name' => 'ООО Чужой']);

        $this->actingAs($manager)->get(route('contractors.index'))
            ->assertOk()
            ->assertSee($own->name)
            ->assertDontSee($other->name);
    }

    /**
     * Менеджер может открыть карточку своего контрагента.
     */
    public function test_manager_can_show_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)->get(route('contractors.show', $contractor))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр карточки чужого контрагента (403).
     */
    public function test_manager_cannot_show_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)->get(route('contractors.show', $contractor))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму редактирования своего контрагента.
     */
    public function test_manager_can_edit_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)->get(route('contractors.edit', $contractor))->assertOk();
    }

    /**
     * Менеджеру запрещено редактировать чужого контрагента (403).
     */
    public function test_manager_cannot_edit_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)->get(route('contractors.edit', $contractor))->assertForbidden();
    }

    /**
     * Админ может создать контрагента и назначить ему менеджера.
     */
    public function test_admin_can_store_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($admin)->post(route('contractors.store'), [
            'name' => 'ООО Ромашка',
            'inn' => '1234567890',
            'type' => 'customer',
            'user_id' => $manager->id,
        ]);

        $contractor = Contractor::where('name', 'ООО Ромашка')->first();
        $this->assertNotNull($contractor);

        $response->assertRedirect(route('contractors.show', $contractor));
        $this->assertDatabaseHas('contractors', ['name' => 'ООО Ромашка', 'user_id' => $manager->id]);
    }

    /**
     * Менеджер может создать контрагента — запись закрепляется за ним.
     */
    public function test_manager_can_store_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->post(route('contractors.store'), [
            'name' => 'ООО Моё',
            'inn' => '1234567890',
            'type' => 'partner',
            'user_id' => $manager->id,
        ]);

        $contractor = Contractor::where('name', 'ООО Моё')->first();
        $this->assertNotNull($contractor);

        $response->assertRedirect(route('contractors.show', $contractor));
        $this->assertDatabaseHas('contractors', ['name' => 'ООО Моё', 'user_id' => $manager->id]);
    }

    /**
     * Менеджер не может назначить контрагента другому менеджеру: user_id подменяется на свой.
     */
    public function test_manager_cannot_assign_contractor_to_other_manager(): void
    {
        $manager = User::factory()->manager()->create();
        $other = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->post(route('contractors.store'), [
            'name' => 'ООО Взлом',
            'inn' => '1234567890',
            'type' => 'customer',
            'user_id' => $other->id,
        ]);

        $contractor = Contractor::where('name', 'ООО Взлом')->first();
        $this->assertNotNull($contractor);

        $response->assertRedirect(route('contractors.show', $contractor));
        $this->assertDatabaseHas('contractors', ['name' => 'ООО Взлом', 'user_id' => $manager->id]);
        $this->assertDatabaseMissing('contractors', ['name' => 'ООО Взлом', 'user_id' => $other->id]);
    }

    /**
     * Контрагент мягко удаляется (попадает в корзину).
     */
    public function test_contractor_can_be_soft_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)->delete(route('contractors.destroy', $contractor))
            ->assertRedirect(route('contractors.index'));

        $this->assertSoftDeleted('contractors', ['id' => $contractor->id]);
    }

    /**
     * Админ видит корзину с удалёнными контрагентами.
     */
    public function test_admin_can_view_trashed(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => 'Удалённый']);
        $contractor->delete();

        $this->actingAs($admin)->get(route('contractors.trashed'))
            ->assertOk()
            ->assertSee('Удалённый');
    }

    /**
     * Менеджеру запрещён доступ к корзине (403).
     */
    public function test_manager_cannot_view_trashed(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('contractors.trashed'))->assertForbidden();
    }

    /**
     * Админ может восстановить контрагента из корзины.
     */
    public function test_admin_can_restore_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $contractor->delete();

        $this->actingAs($admin)->post(route('contractors.restore', $contractor->id))
            ->assertRedirect(route('contractors.trashed'));

        $this->assertNotSoftDeleted('contractors', ['id' => $contractor->id]);
    }

    /**
     * ИНН должен содержать 10 или 12 цифр.
     */
    public function test_inn_must_be_valid_format(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('contractors.store'), [
            'name' => 'ООО Тест',
            'inn' => '123',
            'type' => 'customer',
            'user_id' => $admin->id,
        ])->assertSessionHasErrors(['inn']);
    }

    /**
     * ИНН должен быть уникален.
     */
    public function test_inn_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Contractor::factory()->for($admin, 'user')->create(['inn' => '1234567890']);

        $this->actingAs($admin)->post(route('contractors.store'), [
            'name' => 'ООО Дубль',
            'inn' => '1234567890',
            'type' => 'customer',
            'user_id' => $admin->id,
        ])->assertSessionHasErrors(['inn']);
    }

    /**
     * Админ может создать контрагента без менеджера (user_id необязателен).
     */
    public function test_admin_can_store_contractor_without_manager(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('contractors.store'), [
            'name' => 'ООО Без Менеджера',
            'inn' => '1234567890',
            'type' => 'customer',
        ]);

        $contractor = Contractor::where('name', 'ООО Без Менеджера')->first();
        $this->assertNotNull($contractor);

        $response->assertRedirect(route('contractors.show', $contractor));
        $this->assertNull($contractor->user_id);
    }

    /**
     * Админ может отвязать менеджера при редактировании (обнулить user_id).
     */
    public function test_admin_can_detach_manager_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($admin)->put(route('contractors.update', $contractor), [
            'name' => $contractor->name,
            'inn' => $contractor->inn,
            'type' => 'customer',
            'user_id' => '',
        ])->assertRedirect(route('contractors.show', $contractor));

        $this->assertNull($contractor->fresh()->user_id);
    }

    /**
     * В селекте менеджеров только пользователи с ролью manager (админа там быть не должно).
     */
    public function test_create_form_lists_only_managers(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($admin)->get(route('contractors.create'));

        $response->assertSee($manager->name, false);
        $response->assertDontSee('<option value="'.$admin->id.'"', false);
    }
}
