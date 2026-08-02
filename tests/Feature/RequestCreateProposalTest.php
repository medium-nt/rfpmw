<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Item;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\RequestItem;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCreateProposalTest extends TestCase
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
     * Менеджер создаёт КП из своего запроса с позициями (happy path).
     */
    public function test_manager_can_create_proposal_from_own_request_with_items(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        // Создаём 2 позиции: одна с ценой, вторая без цены
        $item1 = Item::factory()->create();
        $item2 = Item::factory()->create();

        RequestItem::factory()->create([
            'request_id' => $request->id,
            'item_id' => $item1->id,
            'quantity' => 3,
            'price' => 10.50,
        ]);

        RequestItem::factory()->create([
            'request_id' => $request->id,
            'item_id' => $item2->id,
            'quantity' => 2,
            'price' => null,
        ]);

        $response = $this->actingAs($manager)
            ->post(route('requests.create-proposal', $request));

        $response->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        $proposal = Proposal::where('request_id', $request->id)->first();

        $this->assertNotNull($proposal);
        $this->assertSame($request->id, $proposal->request_id);
        $this->assertSame($request->employed_person_id, $proposal->employed_person_id);
        $this->assertSame($manager->id, $proposal->user_id);
        $this->assertSame('draft', $proposal->status);
        $this->assertSame(Carbon::today()->toDateString(), $proposal->date->toDateString());
        $this->assertSame($request->comment, $proposal->comment);

        // Проверяем первую позицию (с ценой)
        $this->assertDatabaseHas('proposal_items', [
            'proposal_id' => $proposal->id,
            'item_id' => $item1->id,
            'quantity' => 3,
            'price' => 10.50,
        ]);

        // Проверяем вторую позицию (price=null стало 0)
        $this->assertDatabaseHas('proposal_items', [
            'proposal_id' => $proposal->id,
            'item_id' => $item2->id,
            'quantity' => 2,
            'price' => 0.0,
        ]);

        // usd_value = 3 * 10.50 + 2 * 0 = 31.50
        $this->assertSame(31.50, (float) $proposal->fresh()->usd_value);
    }

    /**
     * Менеджер создаёт КП из запроса без позиций (usd_value = 0).
     */
    public function test_manager_can_create_proposal_from_request_without_items(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        $response = $this->actingAs($manager)
            ->post(route('requests.create-proposal', $request));

        $response->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        $proposal = Proposal::where('request_id', $request->id)->first();

        $this->assertSame(0.0, (float) $proposal->usd_value);
        $this->assertDatabaseCount('proposal_items', 0);
    }

    /**
     * Повторное создание КП из того же запроса запрещено (403).
     */
    public function test_cannot_create_proposal_twice_from_same_request(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        // Первое создание
        $this->actingAs($manager)
            ->post(route('requests.create-proposal', $request))
            ->assertSessionHasNoErrors();

        // Повторное создание должно вернуть 403
        $this->actingAs($manager)
            ->post(route('requests.create-proposal', $request))
            ->assertForbidden();

        // Проверяем, что создано только одно КП
        $this->assertDatabaseCount('proposals', 1);
    }

    /**
     * Менеджер не может создать КП из чужого запроса (403).
     */
    public function test_manager_cannot_create_proposal_from_other_managers_request(): void
    {
        $managerA = User::factory()->manager()->create();
        $managerB = User::factory()->manager()->create();

        // Запрос принадлежит менеджеру B
        $request = $this->requestFor(Contractor::factory()->for($managerB, 'user')->create());

        // Менеджер A пытается создать КП
        $this->actingAs($managerA)
            ->post(route('requests.create-proposal', $request))
            ->assertForbidden();

        // КП не должно быть создано
        $this->assertDatabaseCount('proposals', 0);
    }

    /**
     * Админ может создать КП из любого запроса (в том числе чужого).
     */
    public function test_admin_can_create_proposal_from_any_request(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        // Запрос принадлежит менеджеру
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        $response = $this->actingAs($admin)
            ->post(route('requests.create-proposal', $request));

        $response->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        $this->assertDatabaseHas('proposals', [
            'request_id' => $request->id,
            'user_id' => $admin->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Нельзя создать КП из запроса с удалённым контрагентом (404).
     */
    public function test_cannot_create_proposal_from_request_with_deleted_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();
        $request = $this->requestFor($contractor);

        // Мягко удаляем контрагента
        $contractor->delete();

        $this->actingAs($manager)
            ->post(route('requests.create-proposal', $request))
            ->assertNotFound();

        // КП не должно быть создано
        $this->assertDatabaseCount('proposals', 0);
    }

    /**
     * Гость (неавторизованный) перенаправляется на логин при попытке создать КП.
     */
    public function test_guest_redirected_to_login_when_creating_proposal(): void
    {
        $manager = User::factory()->manager()->create();
        $request = $this->requestFor(Contractor::factory()->for($manager, 'user')->create());

        $this->post(route('requests.create-proposal', $request))
            ->assertRedirect(route('login'));

        // КП не должно быть создано
        $this->assertDatabaseCount('proposals', 0);
    }

    /**
     * После soft-delete КП можно создать новое из того же запроса (request_id отвязывается).
     */
    public function test_can_recreate_proposal_after_deleting_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());

        // 1. Создаём первое КП из запроса
        $response = $this->actingAs($admin)
            ->post(route('requests.create-proposal', $request));

        $response->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        $firstProposal = Proposal::where('request_id', $request->id)->first();
        $this->assertNotNull($firstProposal);
        $firstProposalId = $firstProposal->id;

        // 2. Удаляем КП (soft-delete + отвязка request_id)
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('proposals.destroy', $firstProposal));

        $deleteResponse->assertRedirect(route('proposals.index'))
            ->assertSessionHas('success', 'КП удалено.');

        // Проверяем, что КП мягко удалено и request_id отвязан
        $deletedProposal = Proposal::onlyTrashed()->find($firstProposalId);
        $this->assertNotNull($deletedProposal);
        $this->assertNull($deletedProposal->request_id, 'request_id должен быть null после удаления');

        // 3. Снова создаём КП из того же запроса (должно успешно создаться)
        $secondResponse = $this->actingAs($admin)
            ->post(route('requests.create-proposal', $request));

        $secondResponse->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        // 4. Проверяем, что в активных записях только одно КП с этим request_id
        $this->assertSame(1, Proposal::withoutTrashed()->where('request_id', $request->id)->count());

        // Проверяем, что старое КП по-прежнему в корзине с null request_id
        $stillDeleted = Proposal::onlyTrashed()->find($firstProposalId);
        $this->assertNotNull($stillDeleted);
        $this->assertNull($stillDeleted->request_id);
    }

    /**
     * При soft-delete запроса отвязываются все связанные КП (request_id = null).
     */
    public function test_deleting_request_detaches_proposal(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->requestFor(Contractor::factory()->for($admin, 'user')->create());

        // 1. Создаём КП из запроса
        $this->actingAs($admin)
            ->post(route('requests.create-proposal', $request))
            ->assertRedirect(route('proposals.show', Proposal::latest('id')->first()))
            ->assertSessionHas('success', 'КП создано из запроса.');

        $proposal = Proposal::where('request_id', $request->id)->first();
        $this->assertNotNull($proposal);
        $proposalId = $proposal->id;

        // 2. Удаляем запрос (soft-delete + отвязка request_id у всех КП)
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('requests.destroy', $request));

        $deleteResponse->assertRedirect(route('requests.index'))
            ->assertSessionHas('success', 'Запрос удалён.');

        // 3. Проверяем, что КП теперь имеет request_id = null (отвязано)
        $proposal = Proposal::find($proposalId);
        $this->assertNotNull($proposal, 'КП должно существовать (не удалялось)');
        $this->assertNull($proposal->request_id, 'request_id должен быть null после удаления запроса');
    }

    /**
     * Создаёт запрос для заданного контрагента с сотрудником и автором.
     */
    private function requestFor(Contractor $contractor): Request
    {
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);

        return Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $contractor->user_id,
        ]);
    }
}
