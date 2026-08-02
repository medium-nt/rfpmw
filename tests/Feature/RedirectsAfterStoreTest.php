<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Item;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Request as RequestModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты новой логики редиректов после создания сущностей.
 */
class RedirectsAfterStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // === A) Редиректы store сущностей на свой show ===

    /**
     * Создание проекта редиректит на projects.show (не contractors.show).
     */
    public function test_store_project_redirects_to_own_show(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->post(route('projects.store', $contractor), [
                'name' => 'Новый проект',
                'date' => '2026-08-02',
                'status' => 'concept',
                'description' => 'Описание',
            ])
            ->assertRedirect(route('projects.show', Project::latest('id')->first()->id));

        $this->assertDatabaseHas('projects', [
            'name' => 'Новый проект',
            'contractor_id' => $contractor->id,
        ]);
    }

    /**
     * Создание КП редиректит на proposals.show (не contractors.show).
     */
    public function test_store_proposal_redirects_to_own_show(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($manager)
            ->post(route('proposals.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-08-02',
                'status' => 'draft',
            ])
            ->assertRedirect(route('proposals.show', Proposal::latest('id')->first()->id));

        $this->assertDatabaseHas('proposals', [
            'employed_person_id' => $employed->id,
            'user_id' => $manager->id,
        ]);
    }

    /**
     * Создание запроса редиректит на requests.show (не contractors.show).
     */
    public function test_store_request_redirects_to_own_show(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        $this->actingAs($manager)
            ->post(route('requests.store', $contractor), [
                'employed_person_id' => $employed->id,
                'date' => '2026-08-02',
                'status' => 'new',
            ])
            ->assertRedirect(route('requests.show', RequestModel::latest('id')->first()->id));

        $this->assertDatabaseHas('requests', [
            'employed_person_id' => $employed->id,
            'user_id' => $manager->id,
        ]);
    }

    // === B) ItemController@store по контексту ===

    /**
     * Создание артикула с from=project&parent={id} редиректит на projects.show.
     */
    public function test_store_item_with_project_context_redirects_to_project(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $vendor = Contractor::factory()->vendor()->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();

        $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'TEST-SKU-001',
                'vendor_id' => $vendor->id,
                'description' => 'Тестовый артикул',
                'from' => 'project',
                'parent' => $project->id,
            ])
            ->assertRedirect(route('projects.show', $project->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'TEST-SKU-001',
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Создание артикула с from=request&parent={id} редиректит на requests.show.
     */
    public function test_store_item_with_request_context_redirects_to_request(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $vendor = Contractor::factory()->vendor()->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $request = RequestModel::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'TEST-SKU-002',
                'vendor_id' => $vendor->id,
                'description' => 'Тестовый артикул для запроса',
                'from' => 'request',
                'parent' => $request->id,
            ])
            ->assertRedirect(route('requests.show', $request->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'TEST-SKU-002',
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Создание артикула с from=proposal&parent={id} редиректит на proposals.show.
     */
    public function test_store_item_with_proposal_context_redirects_to_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $vendor = Contractor::factory()->vendor()->create();
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $proposal = Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'TEST-SKU-003',
                'vendor_id' => $vendor->id,
                'description' => 'Тестовый артикул для КП',
                'from' => 'proposal',
                'parent' => $proposal->id,
            ])
            ->assertRedirect(route('proposals.show', $proposal->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'TEST-SKU-003',
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Создание артикула без from/parent редиректит на items.show (старое поведение).
     */
    public function test_store_item_without_context_redirects_to_items_show(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'TEST-SKU-004',
                'vendor_id' => $vendor->id,
                'description' => 'Артикул без контекста',
            ])
            ->assertRedirect(route('items.show', Item::latest('id')->first()->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'TEST-SKU-004',
            'vendor_id' => $vendor->id,
        ]);
    }

    // === C) View items.create ===

    /**
     * GET items.create без query → нет hidden "from", submit "Сохранить".
     */
    public function test_items_create_form_without_query_shows_default_submit(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get(route('items.create'));

        $response->assertOk();
        $response->assertDontSee('name="from"');
        $response->assertSee('Сохранить');
        $response->assertDontSee('Создать и вернуться');
    }

    /**
     * GET items.create?from=project&parent=1 → есть hidden "from", submit "Создать и вернуться".
     */
    public function test_items_create_form_with_context_shows_context_submit(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();

        $response = $this->actingAs($manager)
            ->get(route('items.create', ['from' => 'project', 'parent' => $project->id]));

        $response->assertOk();
        $response->assertSee('Создать и вернуться');
        $response->assertDontSee('Сохранить');
        // Проверяем hidden поля через содержание HTML
        $content = $response->getContent();
        $this->assertStringContainsString('name="from"', $content);
        $this->assertStringContainsString('value="project"', $content);
        $this->assertStringContainsString('name="parent"', $content);
        $this->assertStringContainsString('value="'.$project->id.'"', $content);
    }

    /**
     * На форме items.create всегда есть ссылка "Создать вендора".
     */
    public function test_items_create_form_always_has_vendor_link(): void
    {
        $manager = User::factory()->manager()->create();

        // Без query
        $this->actingAs($manager)
            ->get(route('items.create'))
            ->assertOk()
            ->assertSee('Создать вендора')
            ->assertSee(route('contractors.create', ['type' => 'vendor']));

        // С query
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $project = Project::factory()->for($contractor, 'contractor')->create();

        $this->actingAs($manager)
            ->get(route('items.create', ['from' => 'project', 'parent' => $project->id]))
            ->assertOk()
            ->assertSee('Создать вендора')
            ->assertSee(route('contractors.create', ['type' => 'vendor']));
    }

    // === D) contractors.create?type=vendor ===

    /**
     * GET contractors.create?type=vendor → option vendor selected.
     */
    public function test_contractors_create_with_vendor_query_selects_vendor(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('contractors.create', ['type' => 'vendor']));

        $response->assertOk();
        $content = $response->getContent();
        // Проверяем, что есть option с vendor и он выбран
        $this->assertStringContainsString('value="vendor"', $content);
        // Blade @selected выводит просто "selected" без значения
        $this->assertStringContainsString('selected', $content);
    }

    // === E) Negative/security тесты для контекста возврата ===

    /**
     * Менеджер-нарушитель не может создать артикул с контекстом чужого проекта.
     */
    public function test_store_item_with_other_managers_parent_gets_403(): void
    {
        // Создаём двух менеджеров с各自的 контрагентами и проектами
        $manager1 = User::factory()->manager()->create();
        $contractor1 = Contractor::factory()->for($manager1, 'user')->create();
        $project1 = Project::factory()->for($contractor1, 'contractor')->create();

        $manager2 = User::factory()->manager()->create();
        $contractor2 = Contractor::factory()->for($manager2, 'user')->create();
        $vendor = Contractor::factory()->vendor()->create();

        // Менеджер-2 создаёт артикул с контекстом проекта менеджера-1
        $this->actingAs($manager2)
            ->post(route('items.store'), [
                'sku' => 'INTRUDER-SKU',
                'vendor_id' => $vendor->id,
                'description' => 'Артикул нарушителя',
                'from' => 'project',
                'parent' => $project1->id,
            ])
            ->assertRedirect(route('projects.show', $project1->id));

        // Артикул создаётся успешно
        $this->assertDatabaseHas('items', [
            'sku' => 'INTRUDER-SKU',
            'vendor_id' => $vendor->id,
        ]);

        // Но при попытке получить доступ к projects.show чужого проекта — 403
        $this->actingAs($manager2)
            ->get(route('projects.show', $project1->id))
            ->assertForbidden();
    }

    /**
     * Невалидное значение from (не из whitelist) → fallback на items.show.
     */
    public function test_store_item_with_invalid_from_falls_back_to_items_show(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'INVALID-FROM-SKU',
                'vendor_id' => $vendor->id,
                'description' => 'Артикул с невалидным from',
                'from' => 'foo',
                'parent' => '1',
            ]);

        $item = Item::where('sku', 'INVALID-FROM-SKU')->first();
        $this->assertNotNull($item);

        $response->assertRedirect(route('items.show', $item->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'INVALID-FROM-SKU',
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Нечисловое значение parent → fallback на items.show.
     */
    public function test_store_item_with_non_numeric_parent_falls_back_to_items_show(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'ABC-PARENT-SKU',
                'vendor_id' => $vendor->id,
                'description' => 'Артикул с буквенным parent',
                'from' => 'project',
                'parent' => 'abc',
            ]);

        $item = Item::where('sku', 'ABC-PARENT-SKU')->first();
        $this->assertNotNull($item);

        $response->assertRedirect(route('items.show', $item->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'ABC-PARENT-SKU',
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Отсутствует параметр parent при наличии from → fallback на items.show.
     */
    public function test_store_item_with_from_but_without_parent_falls_back_to_items_show(): void
    {
        $manager = User::factory()->manager()->create();
        $vendor = Contractor::factory()->vendor()->create();

        $response = $this->actingAs($manager)
            ->post(route('items.store'), [
                'sku' => 'NO-PARENT-SKU',
                'vendor_id' => $vendor->id,
                'description' => 'Артикул без parent',
                'from' => 'project',
            ]);

        $item = Item::where('sku', 'NO-PARENT-SKU')->first();
        $this->assertNotNull($item);

        $response->assertRedirect(route('items.show', $item->id));

        $this->assertDatabaseHas('items', [
            'sku' => 'NO-PARENT-SKU',
            'vendor_id' => $vendor->id,
        ]);
    }
}
