<?php

namespace App\Http\Controllers;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Проверка доступа к контрагенту: менеджер работает только со своими контрагентами.
     */
    protected function authorizeContractorAccess(Contractor $contractor): void
    {
        if (auth()->user()->isManager() && $contractor->user_id !== auth()->id()) {
            abort(403, 'Вы можете работать только со своими контрагентами.');
        }
    }

    /**
     * Проверка доступа к контактному лицу: менеджер видит/правит только людей своих контрагентов.
     */
    protected function authorizeContactPersonAccess(ContactPerson $person): void
    {
        $hasOwnContractor = $person->employedPeople()
            ->whereHas('contractor', fn ($q) => $q->where('user_id', auth()->id()))
            ->exists();

        if (auth()->user()->isManager() && ! $hasOwnContractor) {
            abort(403, 'Вы можете работать только с контактными лицами своих контрагентов.');
        }
    }

    /**
     * Проверка доступа к проекту: менеджер работает только с проектами своих контрагентов.
     */
    protected function authorizeProjectAccess(Project $project): void
    {
        if (auth()->user()->isManager() && $project->contractor->user_id !== auth()->id()) {
            abort(403, 'Вы можете работать только с проектами своих контрагентов.');
        }
    }

    /**
     * Проверка доступа к запросу: менеджер работает только с запросами своих контрагентов.
     */
    protected function authorizeRequestAccess(Request $request): void
    {
        if (auth()->user()->isManager() && $request->employedPerson->contractor->user_id !== auth()->id()) {
            abort(403, 'Вы можете работать только с запросами своих контрагентов.');
        }
    }
}
