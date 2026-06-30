<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Item;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalItemManagementTest extends TestCase
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
     * Админ может создать позицию КП с валидными данными.
     */
    public function test_admin_can_store_proposal_item(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => $item->id,
                'quantity' => 10,
                'price' => 150.50,
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        $proposalItem = ProposalItem::where('proposal_id', $proposal->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($proposalItem);
        $this->assertSame(10, $proposalItem->quantity);
        $this->assertSame(150.50, (float) $proposalItem->price);

        // Проверка пересчёта usd_value КП
        $proposal->refresh();
        $this->assertSame(1505.00, (float) $proposal->usd_value); // 10 * 150.50
    }

    /**
     * Админ может обновить позицию КП.
     */
    public function test_admin_can_update_proposal_item(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create([
            'quantity' => 5,
            'price' => 100.00,
        ]);
        $originalItemId = $proposalItem->item_id;
        $anotherItem = Item::factory()->create();

        $this->actingAs($admin)
            ->put(route('proposal-items.update', [$proposal, $proposalItem]), [
                'item_id' => $anotherItem->id, // Попытка сменить артикул (игнорируется)
                'quantity' => 20,
                'price' => 200.75,
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        // item_id НЕ должен измениться (не валидируется в UpdateProposalItemRequest)
        $proposalItem->refresh();
        $this->assertSame($originalItemId, $proposalItem->item_id);
        $this->assertSame(20, $proposalItem->quantity);
        $this->assertSame(200.75, (float) $proposalItem->price);

        // Проверка пересчёта usd_value КП
        $proposal->refresh();
        $this->assertSame(4015.00, (float) $proposal->usd_value); // 20 * 200.75
    }

    /**
     * Админ может удалить позицию КП.
     */
    public function test_admin_can_destroy_proposal_item(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create([
            'quantity' => 3,
            'price' => 50.00,
        ]);

        $this->actingAs($admin)
            ->delete(route('proposal-items.destroy', [$proposal, $proposalItem]))
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertDatabaseMissing('proposal_items', [
            'id' => $proposalItem->id,
        ]);

        // Проверка пересчёта usd_value КП (должен стать 0)
        $proposal->refresh();
        $this->assertSame(0.0, (float) $proposal->usd_value);
    }

    /**
     * Админ может открыть форму редактирования позиции.
     */
    public function test_admin_can_edit_proposal_item(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($admin)
            ->get(route('proposal-items.edit', [$proposal, $proposalItem]))
            ->assertOk();
    }

    /**
     * Валидация: item_id обязателен при создании.
     */
    public function test_item_id_is_required_on_store(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('proposal-items.store', $proposal), [
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
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => 99999,
                'quantity' => 10,
            ])
            ->assertSessionHasErrors(['item_id']);
    }

    /**
     * Валидация: quantity обязателен при создании.
     */
    public function test_quantity_is_required_on_store(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => $item->id,
                'price' => 100.00,
            ])
            ->assertSessionHasErrors(['quantity']);
    }

    /**
     * Валидация: price обязателен при создании для КП.
     */
    public function test_price_is_required_on_store(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => $item->id,
                'quantity' => 5,
                // price не передаётся — должно FAILED валидацию
            ])
            ->assertSessionHasErrors(['price']);
    }

    /**
     * Валидация: quantity обязателен при обновлении.
     */
    public function test_quantity_is_required_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($admin)
            ->put(route('proposal-items.update', [$proposal, $proposalItem]), [
                'price' => 100.00,
            ])
            ->assertSessionHasErrors(['quantity']);
    }

    /**
     * Валидация: price обязателен при обновлении для КП.
     */
    public function test_price_is_required_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($admin)
            ->put(route('proposal-items.update', [$proposal, $proposalItem]), [
                'quantity' => 10,
            ])
            ->assertSessionHasErrors(['price']);
    }

    /**
     * Менеджер-владелец КП может создавать позиции.
     */
    public function test_manager_owner_can_store_proposal_item(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => $item->id,
                'quantity' => 5,
                'price' => 99.99,
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertDatabaseHas('proposal_items', [
            'proposal_id' => $proposal->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Менеджер-владелец КП может обновлять позиции.
     */
    public function test_manager_owner_can_update_proposal_item(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->put(route('proposal-items.update', [$proposal, $proposalItem]), [
                'quantity' => 15,
                'price' => 175.25,
            ])
            ->assertRedirect(route('proposals.show', $proposal));

        $proposalItem->refresh();
        $this->assertSame(15, $proposalItem->quantity);
        $this->assertSame(175.25, (float) $proposalItem->price);
    }

    /**
     * Менеджер-владелец КП может удалять позиции.
     */
    public function test_manager_owner_can_destroy_proposal_item(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->delete(route('proposal-items.destroy', [$proposal, $proposalItem]))
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertDatabaseMissing('proposal_items', [
            'id' => $proposalItem->id,
        ]);
    }

    /**
     * Менеджер-владелец КП может редактировать позиции.
     */
    public function test_manager_owner_can_edit_proposal_item(): void
    {
        $manager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($manager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->get(route('proposal-items.edit', [$proposal, $proposalItem]))
            ->assertOk();
    }

    /**
     * Менеджер не может создавать позиции в чужом КП (403).
     */
    public function test_manager_cannot_store_item_in_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($otherManager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('proposal-items.store', $proposal), [
                'item_id' => $item->id,
                'quantity' => 10,
                'price' => 100.00,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может обновлять позиции в чужом КП (403).
     */
    public function test_manager_cannot_update_item_in_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($otherManager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->put(route('proposal-items.update', [$proposal, $proposalItem]), [
                'quantity' => 20,
                'price' => 100.00,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может удалять позиции в чужом КП (403).
     */
    public function test_manager_cannot_destroy_item_in_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($otherManager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->delete(route('proposal-items.destroy', [$proposal, $proposalItem]))
            ->assertForbidden();
    }

    /**
     * Менеджер не может редактировать позиции в чужом КП (403).
     */
    public function test_manager_cannot_edit_item_in_other_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $proposal = $this->proposalFor(Contractor::factory()->for($otherManager, 'user')->create());
        $proposalItem = ProposalItem::factory()->for($proposal)->create();

        $this->actingAs($manager)
            ->get(route('proposal-items.edit', [$proposal, $proposalItem]))
            ->assertForbidden();
    }

    /**
     * Чужая позиция через URL другого КП возвращает 404.
     */
    public function test_accessing_item_from_different_proposal_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $proposalA = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalB = $this->proposalFor(Contractor::factory()->for($admin, 'user')->create());
        $proposalItemA = ProposalItem::factory()->for($proposalA)->create();

        // Попытка редактировать позицию КП A через URL КП B
        $this->actingAs($admin)
            ->get(route('proposal-items.edit', [$proposalB, $proposalItemA]))
            ->assertNotFound();
    }

    /**
     * usd_value корректно пересчитывается для нескольких позиций.
     */
    public function test_usd_value_is_correctly_calculated_for_multiple_items(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        // Создаём пустое КП через Proposal::create()
        $proposal = Proposal::create([
            'employed_person_id' => EmployedPerson::factory()->for($contractor)->create()->id,
            'user_id' => $admin->id,
            'date' => now(),
        ]);

        // Создаём две позиции с конкретными значениями
        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 5,
            'price' => 100.00,
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 3,
            'price' => 50.00,
        ]);

        // Пересчитываем usd_value (как это делает контроллер)
        $proposal->recalcUsdValue();

        $expectedUsdValue = (5 * 100.00) + (3 * 50.00); // 500 + 150 = 650
        $this->assertSame($expectedUsdValue, (float) $proposal->usd_value);
    }

    /**
     * Создаёт КП для заданного контрагента.
     */
    private function proposalFor(Contractor $contractor): Proposal
    {
        return Proposal::factory()->create([
            'employed_person_id' => EmployedPerson::factory()->for($contractor)->create()->id,
            'user_id' => $contractor->user_id,
        ]);
    }
}
