<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Item;
use App\Models\Request;
use App\Models\RequestItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestItemManagementTest extends TestCase
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
     * Админ может создать позицию запроса с валидными данными.
     */
    public function test_admin_can_store_request_item(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('request-items.store', $request), [
                'item_id' => $item->id,
                'quantity' => 10,
                'price' => 150.50,
            ])
            ->assertRedirect(route('requests.show', $request));

        $requestItem = RequestItem::where('request_id', $request->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($requestItem);
        $this->assertSame(10, $requestItem->quantity);
        $this->assertSame(150.50, (float) $requestItem->price);

        // Проверка пересчёта usd_value запроса
        $request->refresh();
        $this->assertSame(1505.00, (float) $request->usd_value); // 10 * 150.50
    }

    /**
     * Админ может обновить позицию запроса.
     */
    public function test_admin_can_update_request_item(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create([
            'quantity' => 5,
            'price' => 100.00,
        ]);
        $originalItemId = $requestItem->item_id;
        $anotherItem = Item::factory()->create();

        $this->actingAs($admin)
            ->put(route('request-items.update', [$request, $requestItem]), [
                'item_id' => $anotherItem->id, // Попытка сменить артикул (игнорируется)
                'quantity' => 20,
                'price' => 200.75,
            ])
            ->assertRedirect(route('requests.show', $request));

        // item_id НЕ должен измениться (не валидируется в UpdateRequestItemRequest)
        $requestItem->refresh();
        $this->assertSame($originalItemId, $requestItem->item_id);
        $this->assertSame(20, $requestItem->quantity);
        $this->assertSame(200.75, (float) $requestItem->price);

        // Проверка пересчёта usd_value запроса
        $request->refresh();
        $this->assertSame(4015.00, (float) $request->usd_value); // 20 * 200.75
    }

    /**
     * Админ может удалить позицию запроса.
     */
    public function test_admin_can_destroy_request_item(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create([
            'quantity' => 3,
            'price' => 50.00,
        ]);

        $this->actingAs($admin)
            ->delete(route('request-items.destroy', [$request, $requestItem]))
            ->assertRedirect(route('requests.show', $request));

        $this->assertDatabaseMissing('request_items', [
            'id' => $requestItem->id,
        ]);

        // Проверка пересчёта usd_value запроса (должен стать 0)
        $request->refresh();
        $this->assertSame(0.0, (float) $request->usd_value);
    }

    /**
     * Админ может открыть форму редактирования позиции.
     */
    public function test_admin_can_edit_request_item(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($admin)
            ->get(route('request-items.edit', [$request, $requestItem]))
            ->assertOk();
    }

    /**
     * Валидация: item_id обязателен при создании.
     */
    public function test_item_id_is_required_on_store(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('request-items.store', $request), [
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
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());

        $this->actingAs($admin)
            ->post(route('request-items.store', $request), [
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
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('request-items.store', $request), [
                'item_id' => $item->id,
            ])
            ->assertSessionHasErrors(['quantity']);
    }

    /**
     * Валидация: price может быть пустым (nullable) для запроса.
     */
    public function test_price_can_be_nullable(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->post(route('request-items.store', $request), [
                'item_id' => $item->id,
                'quantity' => 5,
                // price не передаётся — должно пройти валидацию
            ])
            ->assertRedirect(route('requests.show', $request));

        $requestItem = RequestItem::where('request_id', $request->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($requestItem);
        $this->assertNull($requestItem->price);
    }

    /**
     * Валидация: quantity обязателен при обновлении.
     */
    public function test_quantity_is_required_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($admin)
            ->put(route('request-items.update', [$request, $requestItem]), [
                'price' => 100.00,
            ])
            ->assertSessionHasErrors(['quantity']);
    }

    /**
     * Менеджер-владелец запроса может создавать позиции.
     */
    public function test_manager_owner_can_store_request_item(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('request-items.store', $request), [
                'item_id' => $item->id,
                'quantity' => 5,
                'price' => 99.99,
            ])
            ->assertRedirect(route('requests.show', $request));

        $this->assertDatabaseHas('request_items', [
            'request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Менеджер-владелец запроса может обновлять позиции.
     */
    public function test_manager_owner_can_update_request_item(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->put(route('request-items.update', [$request, $requestItem]), [
                'quantity' => 15,
                'price' => 175.25,
            ])
            ->assertRedirect(route('requests.show', $request));

        $requestItem->refresh();
        $this->assertSame(15, $requestItem->quantity);
        $this->assertSame(175.25, (float) $requestItem->price);
    }

    /**
     * Менеджер-владелец запроса может удалять позиции.
     */
    public function test_manager_owner_can_destroy_request_item(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->delete(route('request-items.destroy', [$request, $requestItem]))
            ->assertRedirect(route('requests.show', $request));

        $this->assertDatabaseMissing('request_items', [
            'id' => $requestItem->id,
        ]);
    }

    /**
     * Менеджер-владелец запроса может редактировать позиции.
     */
    public function test_manager_owner_can_edit_request_item(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->get(route('request-items.edit', [$request, $requestItem]))
            ->assertOk();
    }

    /**
     * Менеджер не может создавать позиции в чужом запросе (403).
     */
    public function test_manager_cannot_store_item_in_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($otherManager, 'user')->create());
        $item = Item::factory()->create();

        $this->actingAs($manager)
            ->post(route('request-items.store', $request), [
                'item_id' => $item->id,
                'quantity' => 10,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может обновлять позиции в чужом запросе (403).
     */
    public function test_manager_cannot_update_item_in_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($otherManager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->put(route('request-items.update', [$request, $requestItem]), [
                'quantity' => 20,
            ])
            ->assertForbidden();
    }

    /**
     * Менеджер не может удалять позиции в чужом запросе (403).
     */
    public function test_manager_cannot_destroy_item_in_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($otherManager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->delete(route('request-items.destroy', [$request, $requestItem]))
            ->assertForbidden();
    }

    /**
     * Менеджер не может редактировать позиции в чужом запросе (403).
     */
    public function test_manager_cannot_edit_item_in_other_request(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($otherManager, 'user')->create());
        $requestItem = RequestItem::factory()->for($request)->create();

        $this->actingAs($manager)
            ->get(route('request-items.edit', [$request, $requestItem]))
            ->assertForbidden();
    }

    /**
     * Чужая позиция через URL другого запроса возвращает 404.
     */
    public function test_accessing_item_from_different_request_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $requestA = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestB = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());
        $requestItemA = RequestItem::factory()->for($requestA)->create();

        // Попытка редактировать позицию запроса A через URL запроса B
        $this->actingAs($admin)
            ->get(route('request-items.edit', [$requestB, $requestItemA]))
            ->assertNotFound();
    }

    /**
     * usd_value корректно пересчитывается для нескольких позиций.
     */
    public function test_usd_value_is_correctly_calculated_for_multiple_items(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        // Создаём пустой запрос через Request::create()
        $request = Request::create([
            'employed_person_id' => EmployedPerson::factory()->for($contractor)->create()->id,
            'user_id' => $admin->id,
            'date' => now(),
        ]);

        // Создаём две позиции с конкретными значениями
        RequestItem::create([
            'request_id' => $request->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 5,
            'price' => 100.00,
        ]);

        RequestItem::create([
            'request_id' => $request->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 3,
            'price' => 50.00,
        ]);

        // Пересчитываем usd_value (как это делает контроллер)
        $request->recalcUsdValue();

        $expectedUsdValue = (5 * 100.00) + (3 * 50.00); // 500 + 150 = 650
        $this->assertSame($expectedUsdValue, (float) $request->usd_value);
    }

    /**
     * Создаёт запрос для заданного контрагента.
     */
    private function requestFor(Contractor $contractor): Request
    {
        return Request::factory()->create([
            'employed_person_id' => EmployedPerson::factory()->for($contractor)->create()->id,
            'user_id' => $contractor->user_id,
        ]);
    }
}
