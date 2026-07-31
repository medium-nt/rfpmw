<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemManagementTest extends TestCase
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
     * Админ видит список артикулов.
     */
    public function test_admin_can_index_items(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'TEST-001']);

        $this->actingAs($admin)->get(route('items.index'))
            ->assertOk()
            ->assertSee($item->sku);
    }

    /**
     * Админ создаёт артикул с валидными данными.
     */
    public function test_admin_can_store_item(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'STM32F103',
            'vendor_id' => $vendor->id,
            'description' => '32-битный микроконтроллер',
        ]);

        $item = Item::where('sku', 'STM32F103')->first();
        $this->assertNotNull($item);

        $response->assertRedirect(route('items.show', $item));
        $this->assertDatabaseHas('items', [
            'sku' => 'STM32F103',
            'vendor_id' => $vendor->id,
            'description' => '32-битный микроконтроллер',
        ]);
    }

    /**
     * Админ видит карточку артикула.
     */
    public function test_admin_can_show_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'SHOW-001']);

        $this->actingAs($admin)->get(route('items.show', $item))
            ->assertOk()
            ->assertSee($item->sku)
            ->assertSee($item->vendor->name);
    }

    /**
     * Админ редактирует артикул.
     */
    public function test_admin_can_update_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'OLD-SKU']);
        $newVendor = Contractor::factory()->vendor()->create();

        $this->actingAs($admin)->put(route('items.update', $item), [
            'sku' => 'NEW-SKU',
            'vendor_id' => $newVendor->id,
            'description' => 'Обновлённое описание',
        ])->assertRedirect(route('items.show', $item));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'sku' => 'NEW-SKU',
            'vendor_id' => $newVendor->id,
            'description' => 'Обновлённое описание',
        ]);
    }

    /**
     * Админ удаляет артикул (soft delete).
     */
    public function test_admin_can_delete_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'DELETE-001']);

        $this->actingAs($admin)->delete(route('items.destroy', $item))
            ->assertRedirect(route('items.index'));

        $this->assertSoftDeleted('items', ['id' => $item->id]);

        // Запись исчезла из списка (без withTrashed)
        $this->actingAs($admin)->get(route('items.index'))
            ->assertOk()
            ->assertDontSee('DELETE-001');
    }

    /**
     * Менеджер видит список артикулов (общий справочник).
     */
    public function test_manager_can_index_items(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'MGR-IDX-001']);

        $this->actingAs($manager)->get(route('items.index'))
            ->assertOk()
            ->assertSee($item->sku);
    }

    /**
     * Менеджер видит форму создания артикула.
     */
    public function test_manager_can_view_create_form(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('items.create'))->assertOk();
    }

    /**
     * Менеджер создаёт артикул с валидными данными.
     */
    public function test_manager_can_store_item(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($manager)->post(route('items.store'), [
            'sku' => 'MGR-STORE-001',
            'vendor_id' => $vendor->id,
            'description' => 'Артикул, созданный менеджером',
        ]);

        $item = Item::where('sku', 'MGR-STORE-001')->first();
        $this->assertNotNull($item);

        $response->assertRedirect(route('items.show', $item));
        $this->assertDatabaseHas('items', [
            'sku' => 'MGR-STORE-001',
            'vendor_id' => $vendor->id,
            'description' => 'Артикул, созданный менеджером',
        ]);
    }

    /**
     * Менеджер видит карточку артикула (общий справочник).
     */
    public function test_manager_can_show_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'MGR-SHOW-001']);

        $this->actingAs($manager)->get(route('items.show', $item))
            ->assertOk()
            ->assertSee($item->sku)
            ->assertSee($item->vendor->name);
    }

    /**
     * Менеджер не имеет доступа к форме редактирования (403).
     */
    public function test_manager_cannot_edit_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create();

        $this->actingAs($manager)->get(route('items.edit', $item))->assertForbidden();
    }

    /**
     * Менеджер не может обновить артикул (403).
     */
    public function test_manager_cannot_update_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $this->actingAs($manager)->put(route('items.update', $item), [
            'sku' => 'HACK-SKU',
            'vendor_id' => $vendor->id,
        ])->assertForbidden();
    }

    /**
     * Менеджер не может удалить артикул (403).
     */
    public function test_manager_cannot_delete_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create();

        $this->actingAs($manager)->delete(route('items.destroy', $item))->assertForbidden();
    }

    /**
     * Валидация: vendor_id обязателен.
     */
    public function test_vendor_id_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'TEST-SKU',
            'vendor_id' => '',
        ])->assertSessionHasErrors(['vendor_id']);
    }

    /**
     * Валидация: vendor_id должен существовать в таблице contractors.
     */
    public function test_vendor_id_must_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'TEST-SKU',
            'vendor_id' => 99999,
        ])->assertSessionHasErrors(['vendor_id']);
    }

    /**
     * Валидация: vendor_id должен быть типа vendor.
     */
    public function test_vendor_id_must_be_vendor_type(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Contractor::factory()->customer()->create();

        $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'TEST-SKU',
            'vendor_id' => $customer->id,
        ])->assertSessionHasErrors(['vendor_id']);
    }

    /**
     * Валидация: sku обязателен.
     */
    public function test_sku_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $this->actingAs($admin)->post(route('items.store'), [
            'sku' => '',
            'vendor_id' => $vendor->id,
        ])->assertSessionHasErrors(['sku']);
    }

    /**
     * Валидация: уникальность пары [sku, vendor_id].
     */
    public function test_sku_must_be_unique_for_vendor(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Contractor::factory()->vendor()->create();

        Item::factory()->create([
            'sku' => 'DUP-SKU',
            'vendor_id' => $vendor->id,
        ]);

        $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'DUP-SKU',
            'vendor_id' => $vendor->id,
        ])->assertSessionHasErrors(['sku']);
    }

    /**
     * Валидация: один и тот же sku у разных вендоров разрешён.
     */
    public function test_same_sku_different_vendor_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor1 = Contractor::factory()->vendor()->create();
        $vendor2 = Contractor::factory()->vendor()->create();

        Item::factory()->create([
            'sku' => 'SAME-SKU',
            'vendor_id' => $vendor1->id,
        ]);

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'sku' => 'SAME-SKU',
            'vendor_id' => $vendor2->id,
        ]);

        $item = Item::where('sku', 'SAME-SKU')->where('vendor_id', $vendor2->id)->first();
        $response->assertRedirect(route('items.show', $item));

        $this->assertDatabaseHas('items', [
            'sku' => 'SAME-SKU',
            'vendor_id' => $vendor2->id,
        ]);
    }

    /**
     * Валидация: update игнорирует текущую запись при проверке уникальности.
     */
    public function test_update_can_keep_same_sku(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'KEEP-SKU']);
        $newVendor = Contractor::factory()->vendor()->create();

        $this->actingAs($admin)->put(route('items.update', $item), [
            'sku' => 'KEEP-SKU',
            'vendor_id' => $newVendor->id,
            'description' => 'Новое описание',
        ])->assertRedirect(route('items.show', $item));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'sku' => 'KEEP-SKU',
            'vendor_id' => $newVendor->id,
        ]);
    }

    /**
     * Валидация: update не позволяет создать дубль с другим артикулом.
     */
    public function test_update_cannot_duplicate_another_item_sku(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $item1 = Item::factory()->create(['sku' => 'EXIST-SKU', 'vendor_id' => $vendor->id]);
        $item2 = Item::factory()->create(['sku' => 'OTHER-SKU', 'vendor_id' => $vendor->id]);

        $this->actingAs($admin)->put(route('items.update', $item2), [
            'sku' => 'EXIST-SKU',
            'vendor_id' => $vendor->id,
        ])->assertSessionHasErrors(['sku']);
    }

    /**
     * Админ видит форму создания с селектом вендоров.
     */
    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Contractor::factory()->vendor()->create(['name' => 'Vendor Inc']);

        $this->actingAs($admin)->get(route('items.create'))
            ->assertOk()
            ->assertSee('Vendor Inc');
    }

    /**
     * Админ видит форму редактирования с текущими данными.
     */
    public function test_admin_can_view_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'EDIT-001']);

        $this->actingAs($admin)->get(route('items.edit', $item))
            ->assertOk()
            ->assertSee('EDIT-001');
    }
}
