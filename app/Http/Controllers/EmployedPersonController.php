<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachExistingContactPersonRequest;
use App\Http\Requests\UpdateEmployedPersonRequest;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use Illuminate\Http\RedirectResponse;

class EmployedPersonController extends Controller
{
    /**
     * Привязка существующего контактного лица к контрагенту.
     *
     * Если пара человек↔контрагент ранее была отвязана (в корзине) — восстанавливается с новой должностью,
     * иначе создаётся новая активная связь. Активный дубликат отсекается валидацией запроса.
     */
    public function store(AttachExistingContactPersonRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $request->validated();
        $position = $data['position'] ?? null;

        $existing = EmployedPerson::withTrashed()
            ->where('contact_person_id', $data['contact_person_id'])
            ->where('contractor_id', $contractor->id)
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->restore();
            $existing->update(['position' => $position]);
        } else {
            EmployedPerson::create([
                'contact_person_id' => $data['contact_person_id'],
                'contractor_id' => $contractor->id,
                'position' => $position,
            ]);
        }

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Контактное лицо привязано.');
    }

    /**
     * Обновление должности контактного лица у контрагента (атрибут связи).
     */
    public function update(UpdateEmployedPersonRequest $request, Contractor $contractor, EmployedPerson $employedPerson): RedirectResponse
    {
        $this->authorizeEmployedBelongsToContractor($contractor, $employedPerson);

        $employedPerson->update($request->validated());

        return redirect()
            ->route('contact-people.show', $employedPerson->contactPerson)
            ->with('success', 'Должность обновлена.');
    }

    /**
     * Отвязка контактного лица от контрагента (жёсткое удаление связи, чтобы освободить уникальную пару для повторной привязки).
     */
    public function destroy(Contractor $contractor, EmployedPerson $employedPerson): RedirectResponse
    {
        $this->authorizeEmployedBelongsToContractor($contractor, $employedPerson);

        $employedPerson->forceDelete();

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Контактное лицо отвязано.');
    }

    /**
     * Проверка доступа к контрагенту и принадлежности связи этому контрагенту.
     *
     * Чужая связь (переданная через URL другого контрагента) трактуется как отсутствующий ресурс.
     */
    protected function authorizeEmployedBelongsToContractor(Contractor $contractor, EmployedPerson $employedPerson): void
    {
        $this->authorizeContractorAccess($contractor);

        if ($employedPerson->contractor_id !== $contractor->id) {
            abort(404);
        }
    }
}
