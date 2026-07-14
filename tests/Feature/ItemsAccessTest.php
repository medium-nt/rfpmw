<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemsAccessTest extends TestCase
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
     * Менеджер может видеть список артикулов.
     */
    public function test_manager_can_view_items_index(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'TEST-001']);

        $this->actingAs($manager)->get(route('items.index'))
            ->assertOk()
            ->assertSee($item->sku);
    }

    /**
     * Менеджер может видеть карточку артикула.
     */
    public function test_manager_can_view_item_show(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'SHOW-001']);

        $this->actingAs($manager)->get(route('items.show', $item))
            ->assertOk()
            ->assertSee($item->sku);
    }

    /**
     * Менеджер может видеть форму создания артикула.
     */
    public function test_manager_can_view_create_form(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('items.create'))
            ->assertOk();
    }

    /**
     * Менеджер может создать артикул с валидными данными.
     */
    public function test_manager_can_store_item(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($manager)->post(route('items.store'), [
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
     * Менеджер не имеет доступа к форме редактирования (403).
     */
    public function test_manager_cannot_view_edit_form(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create();

        $this->actingAs($manager)->get(route('items.edit', $item))
            ->assertForbidden();
    }

    /**
     * Менеджер не может обновить артикул (403).
     */
    public function test_manager_cannot_update_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'OLD-SKU']);
        $newVendor = Contractor::factory()->vendor()->create();

        $this->actingAs($manager)->put(route('items.update', $item), [
            'sku' => 'NEW-SKU',
            'vendor_id' => $newVendor->id,
            'description' => 'Попытка обновления',
        ])->assertForbidden();

        // Данные не изменились
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'sku' => 'OLD-SKU',
        ]);
    }

    /**
     * Менеджер не может удалить артикул (403).
     */
    public function test_manager_cannot_delete_item(): void
    {
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create(['sku' => 'DELETE-001']);

        $this->actingAs($manager)->delete(route('items.destroy', $item))
            ->assertForbidden();

        // Артикул не удалён
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'sku' => 'DELETE-001',
            'deleted_at' => null,
        ]);
    }

    /**
     * Админ может видеть форму редактирования.
     */
    public function test_admin_can_view_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'EDIT-001']);

        $this->actingAs($admin)->get(route('items.edit', $item))
            ->assertOk()
            ->assertSee('EDIT-001');
    }

    /**
     * Админ может обновить артикул.
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
     * Админ может удалить артикул (soft delete).
     */
    public function test_admin_can_delete_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->create(['sku' => 'DELETE-001']);

        $this->actingAs($admin)->delete(route('items.destroy', $item))
            ->assertRedirect(route('items.index'));

        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    /**
     * Гость перенаправляется на login при попытке просмотра списка артикулов.
     */
    public function test_guest_redirected_on_index(): void
    {
        $this->get(route('items.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * Гость перенаправляется на login при попытке создания артикула.
     */
    public function test_guest_redirected_on_create(): void
    {
        $this->get(route('items.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * Гость перенаправляется на login при попытке store.
     */
    public function test_guest_redirected_on_store(): void
    {
        $vendor = Contractor::factory()->vendor()->create();

        $this->post(route('items.store'), [
            'sku' => 'GUEST-SKU',
            'vendor_id' => $vendor->id,
        ])->assertRedirect(route('login'));
    }

    /**
     * Гость перенаправляется на login при попытке редактирования.
     */
    public function test_guest_redirected_on_edit(): void
    {
        $item = Item::factory()->create();

        $this->get(route('items.edit', $item))
            ->assertRedirect(route('login'));
    }
}
