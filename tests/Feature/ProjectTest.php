<?php

namespace Tests\Feature;

use App\Models\EmployedPerson;
use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проект создаётся с контрагентом и необязательным ответственным.
     */
    public function test_can_create_project(): void
    {
        $project = Project::factory()->create();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertNull($project->responsible_person_id);
    }

    /**
     * При жёстком удалении ответственного сотрудника ссылка обнуляется (SET NULL).
     */
    public function test_responsible_person_is_set_null_on_force_delete(): void
    {
        $employed = EmployedPerson::factory()->create();
        $project = Project::factory()->create(['responsible_person_id' => $employed->id]);

        $employed->forceDelete();

        $this->assertNull($project->fresh()->responsible_person_id);
    }

    /**
     * Позиции проекта каскадно удаляются при жёстком удалении проекта.
     */
    public function test_project_items_cascade_on_project_force_delete(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create();
        ProjectItem::factory()->create([
            'project_id' => $project->id,
            'item_id' => $item->id,
        ]);

        $project->forceDelete();

        $this->assertDatabaseMissing('project_item', ['project_id' => $project->id]);
    }

    /**
     * Позиция проекта связана с проектом и артикулом.
     */
    public function test_project_item_relations(): void
    {
        $projectItem = ProjectItem::factory()->create();

        $this->assertInstanceOf(Project::class, $projectItem->project);
        $this->assertInstanceOf(Item::class, $projectItem->item);
    }
}
