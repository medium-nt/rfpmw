<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectItemRequest;
use App\Http\Requests\UpdateProjectItemRequest;
use App\Models\Project;
use App\Models\ProjectItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectItemController extends Controller
{
    /**
     * Добавляет позицию (артикул) к проекту и пересчитывает сумму проекта.
     */
    public function store(StoreProjectItemRequest $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $project->projectItems()->create($request->validated());

        $project->recalcUsdValue();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Позиция добавлена.');
    }

    /**
     * Форма редактирования позиции проекта (артикул неизменяем).
     */
    public function edit(Project $project, ProjectItem $projectItem): View
    {
        $this->authorizeProjectItemAccess($project, $projectItem);

        $projectItem->load('item.vendor');

        return view('project-items.edit', compact('project', 'projectItem'));
    }

    /**
     * Обновляет параметры позиции проекта и пересчитывает сумму.
     */
    public function update(UpdateProjectItemRequest $request, Project $project, ProjectItem $projectItem): RedirectResponse
    {
        $this->authorizeProjectItemAccess($project, $projectItem);

        $projectItem->update($request->validated());

        $project->recalcUsdValue();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Позиция обновлена.');
    }

    /**
     * Удаляет позицию проекта и пересчитывает сумму.
     */
    public function destroy(Project $project, ProjectItem $projectItem): RedirectResponse
    {
        $this->authorizeProjectItemAccess($project, $projectItem);

        $projectItem->delete();

        $project->recalcUsdValue();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Позиция удалена.');
    }

    /**
     * Проверка доступа к проекту и принадлежности позиции этому проекту.
     *
     * Чужая позиция (переданная через URL другого проекта) трактуется как отсутствующий ресурс.
     */
    protected function authorizeProjectItemAccess(Project $project, ProjectItem $projectItem): void
    {
        $this->authorizeProjectAccess($project);

        if ($projectItem->project_id !== $project->id) {
            abort(404);
        }
    }
}
