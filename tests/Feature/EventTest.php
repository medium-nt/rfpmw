<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Событие хранит тип взаимодействия строкой.
     */
    public function test_event_stores_string_type(): void
    {
        $event = Event::factory()->create(['event_type' => 'call']);

        $this->assertSame('call', $event->fresh()->event_type);
    }

    /**
     * Событие без привязок к проекту/запросу/КП создаётся корректно.
     */
    public function test_event_without_relations(): void
    {
        $event = Event::factory()->create();

        $this->assertNull($event->project_id);
        $this->assertNull($event->request_id);
        $this->assertNull($event->proposal_id);
    }

    /**
     * При жёстком удалении привязанного проекта ссылка события обнуляется (SET NULL).
     */
    public function test_project_relation_is_set_null_on_force_delete(): void
    {
        $project = Project::factory()->create();
        $event = Event::factory()->create(['project_id' => $project->id]);

        $project->forceDelete();

        $this->assertNull($event->fresh()->project_id);
    }
}
