<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\Event;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Багфикс: мягко-удалённые контрагенты (в корзине) не должны «просачиваться»
 * в карточки/списки контактных лиц, событий, проектов, запросов и КП —
 * иначе ссылка route('contractors.show') на удалённого даёт 404.
 *
 * Привязки к удалённым контрагентам скрываются; записи сущностей, привязанные
 * к удалённому контрагенту, исключаются из списков, а их карточки отдают 404.
 */
class HiddenTrashedContractorTest extends TestCase
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
     * Карточка контактного лица не показывает привязку к удалённому контрагенту
     * и рендерит пустое состояние «Нет привязок».
     */
    public function test_contact_person_show_hides_deleted_contractor_binding(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => 'ООО УдалённыйКонтрагентАльфа']);
        $person = ContactPerson::factory()->create(['fio' => 'Сидоров Сидор']);
        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);

        $contractor->delete();

        $this->actingAs($admin)
            ->get(route('contact-people.show', $person))
            ->assertOk()
            ->assertSee('Сидоров Сидор')
            ->assertDontSee('ООО УдалённыйКонтрагентАльфа')
            ->assertSee('Нет привязок к контрагентам');
    }

    /**
     * Карточка контактного лица сохраняет активную привязку, когда другую удалили.
     */
    public function test_contact_person_show_keeps_active_binding_when_other_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $trashed = Contractor::factory()->for($admin, 'user')->create(['name' => 'ООО УдалённыйКонтрагентБета']);
        $active = Contractor::factory()->for($admin, 'user')->create(['name' => 'ООО АктивныйКлиентГамма']);

        $person = ContactPerson::factory()->create(['fio' => 'Сидоров Сидор']);
        EmployedPerson::factory()->create(['contact_person_id' => $person->id, 'contractor_id' => $trashed->id]);
        EmployedPerson::factory()->create(['contact_person_id' => $person->id, 'contractor_id' => $active->id]);

        $trashed->delete();

        $this->actingAs($admin)
            ->get(route('contact-people.show', $person))
            ->assertOk()
            ->assertSee('ООО АктивныйКлиентГамма')
            ->assertDontSee('ООО УдалённыйКонтрагентБета')
            ->assertDontSee('Нет привязок к контрагентам');
    }

    /**
     * Список контактных лиц не показывает имя удалённого контрагента в колонке «Контрагенты».
     */
    public function test_contact_person_index_hides_deleted_contractor_name(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => 'ООО УдалённыйКонтрагентДельта']);
        $person = ContactPerson::factory()->create(['fio' => 'Сидоров Сидор']);
        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);

        $contractor->delete();

        $this->actingAs($admin)
            ->get(route('contact-people.index'))
            ->assertOk()
            ->assertDontSee('ООО УдалённыйКонтрагентДельта');
    }

    /**
     * Список событий не показывает события, привязанные к удалённому контрагенту.
     */
    public function test_event_index_hides_events_of_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$trashedEvent, $trashedContractor] = $this->eventForAdmin($admin, 'ООО УдалённыйСобытийный', 'Звонок Удалённый');
        [$activeEvent, $activeContractor] = $this->eventForAdmin($admin, 'ООО АктивныйСобытийный', 'Звонок Активный');

        $trashedContractor->delete();

        $this->actingAs($admin)
            ->get(route('events.index'))
            ->assertOk()
            ->assertDontSee('ООО УдалённыйСобытийный')
            ->assertSee('ООО АктивныйСобытийный');
    }

    /**
     * Карточка события с удалённым контрагентом отдаёт 404.
     */
    public function test_event_show_returns_404_for_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$event, $contractor] = $this->eventForAdmin($admin, 'ООО УдалённыйСобытийный', 'Звонок');

        $contractor->delete();

        $this->actingAs($admin)
            ->get(route('events.show', $event))
            ->assertNotFound();
    }

    /**
     * Список проектов не показывает проекты удалённого контрагента.
     */
    public function test_project_index_hides_projects_of_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $trashed = $this->projectForAdmin($admin, 'ООО УдалённыйПроектный', 'Проект Удалённый');
        $active = $this->projectForAdmin($admin, 'ООО АктивныйПроектный', 'Проект Активный');

        $trashed->contractor->delete();

        $this->actingAs($admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertDontSee('ООО УдалённыйПроектный')
            ->assertSee('ООО АктивныйПроектный');
    }

    /**
     * Карточка проекта с удалённым контрагентом отдаёт 404.
     */
    public function test_project_show_returns_404_for_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->projectForAdmin($admin, 'ООО УдалённыйПроектный', 'Проект');

        $project->contractor->delete();

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertNotFound();
    }

    /**
     * Список запросов не показывает запросы удалённого контрагента.
     */
    public function test_request_index_hides_requests_of_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$trashedRequest, $trashedContractor] = $this->requestForAdmin($admin, 'ООО УдалённыйЗапросный');
        [$activeRequest, $activeContractor] = $this->requestForAdmin($admin, 'ООО АктивныйЗапросный');

        $trashedContractor->delete();

        $this->actingAs($admin)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertDontSee('ООО УдалённыйЗапросный')
            ->assertSee('ООО АктивныйЗапросный');
    }

    /**
     * Карточка запроса с удалённым контрагентом отдаёт 404.
     */
    public function test_request_show_returns_404_for_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$request, $contractor] = $this->requestForAdmin($admin, 'ООО УдалённыйЗапросный');

        $contractor->delete();

        $this->actingAs($admin)
            ->get(route('requests.show', $request))
            ->assertNotFound();
    }

    /**
     * Список КП не показывает КП удалённого контрагента.
     */
    public function test_proposal_index_hides_proposals_of_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$trashedProposal, $trashedContractor] = $this->proposalForAdmin($admin, 'ООО УдалённыйКПшный');
        [$activeProposal, $activeContractor] = $this->proposalForAdmin($admin, 'ООО АктивныйКПшный');

        $trashedContractor->delete();

        $this->actingAs($admin)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertDontSee('ООО УдалённыйКПшный')
            ->assertSee('ООО АктивныйКПшный');
    }

    /**
     * Карточка КП с удалённым контрагентом отдаёт 404.
     */
    public function test_proposal_show_returns_404_for_deleted_contractor(): void
    {
        $admin = User::factory()->admin()->create();
        [$proposal, $contractor] = $this->proposalForAdmin($admin, 'ООО УдалённыйКПшный');

        $contractor->delete();

        $this->actingAs($admin)
            ->get(route('proposals.show', $proposal))
            ->assertNotFound();
    }

    /**
     * Создаёт событие для админа с уникальным именем контрагента и ФИО (маркеры для assert).
     *
     * @return array{0: Event, 1: Contractor}
     */
    private function eventForAdmin(User $admin, string $contractorName, string $fio): array
    {
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => $contractorName]);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create(['fio' => $fio])->id,
            'contractor_id' => $contractor->id,
        ]);
        $event = Event::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
        ]);

        return [$event, $contractor];
    }

    /**
     * Создаёт проект для админа с уникальным именем контрагента и названием проекта (маркеры для assert).
     */
    private function projectForAdmin(User $admin, string $contractorName, string $projectName): Project
    {
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => $contractorName]);

        return Project::factory()->create([
            'contractor_id' => $contractor->id,
            'name' => $projectName,
        ]);
    }

    /**
     * Создаёт запрос для админа с уникальным именем контрагента (маркер для assert).
     *
     * @return array{0: Request, 1: Contractor}
     */
    private function requestForAdmin(User $admin, string $contractorName): array
    {
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => $contractorName]);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $request = Request::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
        ]);

        return [$request, $contractor];
    }

    /**
     * Создаёт КП для админа с уникальным именем контрагента (маркер для assert).
     *
     * @return array{0: Proposal, 1: Contractor}
     */
    private function proposalForAdmin(User $admin, string $contractorName): array
    {
        $contractor = Contractor::factory()->for($admin, 'user')->create(['name' => $contractorName]);
        $employed = EmployedPerson::factory()->create([
            'contact_person_id' => ContactPerson::factory()->create()->id,
            'contractor_id' => $contractor->id,
        ]);
        $proposal = Proposal::factory()->create([
            'employed_person_id' => $employed->id,
            'user_id' => $admin->id,
        ]);

        return [$proposal, $contractor];
    }
}
