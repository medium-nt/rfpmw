<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPersonCrudTest extends TestCase
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
     * Админ видит всех контактных лиц.
     */
    public function test_admin_can_index_all_contact_people(): void
    {
        $admin = User::factory()->admin()->create();

        $own = $this->personAttachedTo(Contractor::factory()->for($admin, 'user')->create());
        $other = $this->personAttachedTo(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($admin)->get(route('contact-people.index'))
            ->assertOk()
            ->assertSee($own->fio)
            ->assertSee($other->fio);
    }

    /**
     * Менеджер видит только людей своих контрагентов (data scoping).
     */
    public function test_manager_can_only_index_own_contact_people(): void
    {
        $manager = User::factory()->manager()->create();

        $own = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());
        $other = $this->personAttachedTo(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('contact-people.index'))
            ->assertOk()
            ->assertSee($own->fio)
            ->assertDontSee($other->fio);
    }

    /**
     * Менеджер не видит контактное лицо без привязки к его контрагентам.
     */
    public function test_manager_cannot_index_unlinked_person(): void
    {
        $manager = User::factory()->manager()->create();
        ContactPerson::factory()->create(['fio' => 'Висящий Безкомпанный']);

        $this->actingAs($manager)->get(route('contact-people.index'))
            ->assertOk()
            ->assertDontSee('Висящий Безкомпанный');
    }

    /**
     * Админ видит контактное лицо даже без привязок к контрагентам.
     */
    public function test_admin_can_index_unlinked_person(): void
    {
        $admin = User::factory()->admin()->create();
        ContactPerson::factory()->create(['fio' => 'Висящий Безкомпанный']);

        $this->actingAs($admin)->get(route('contact-people.index'))
            ->assertOk()
            ->assertSee('Висящий Безкомпанный');
    }

    /**
     * Менеджер может открыть карточку своего контактного лица.
     */
    public function test_manager_can_show_own_contact_person(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('contact-people.show', $person))->assertOk();
    }

    /**
     * Менеджеру запрещён просмотр чужого контактного лица (403).
     */
    public function test_manager_cannot_show_other_contact_person(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('contact-people.show', $person))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму и обновить личные данные своего контактного лица.
     */
    public function test_manager_can_edit_and_update_own_contact_person(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)->get(route('contact-people.edit', $person))->assertOk();

        $this->actingAs($manager)->put(route('contact-people.update', $person), [
            'fio' => 'Новое ФИО',
            'phone' => '+79991234567',
        ])->assertRedirect(route('contact-people.show', $person));

        $this->assertDatabaseHas('contact_people', ['id' => $person->id, 'fio' => 'Новое ФИО']);
    }

    /**
     * Менеджеру запрещено редактировать чужое контактное лицо (403).
     */
    public function test_manager_cannot_edit_other_contact_person(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create());

        $this->actingAs($manager)->get(route('contact-people.edit', $person))->assertForbidden();
    }

    /**
     * Менеджер может открыть форму создания контактного лица для своего контрагента.
     */
    public function test_manager_can_open_create_form_for_own_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for($manager, 'user')->create();

        $this->actingAs($manager)
            ->get(route('contact-people.create', $contractor))
            ->assertOk()
            ->assertSee('Новое контактное лицо');
    }

    /**
     * Менеджеру запрещено создавать контактное лицо для чужого контрагента (403).
     */
    public function test_manager_cannot_open_create_form_for_other_contractor(): void
    {
        $manager = User::factory()->manager()->create();
        $contractor = Contractor::factory()->for(User::factory()->manager()->create(), 'user')->create();

        $this->actingAs($manager)
            ->get(route('contact-people.create', $contractor))
            ->assertForbidden();
    }

    /**
     * Создание нового контактного лица сразу привязывает его к контрагенту.
     */
    public function test_admin_can_store_new_person_and_attach(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Иванов Иван',
                'phone' => '+79990000000',
                'email' => 'ivan@example.com',
                'interests' => 'Сварка',
                'position' => 'Менеджер по закупкам',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $person = ContactPerson::where('fio', 'Иванов Иван')->first();
        $this->assertNotNull($person);
        $this->assertDatabaseHas('employed_people', [
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
            'position' => 'Менеджер по закупкам',
        ]);
    }

    /**
     * Создаёт контактное лицо и привязывает его к заданному контрагенту, возвращая свежую модель.
     */
    private function personAttachedTo(Contractor $contractor): ContactPerson
    {
        $person = ContactPerson::factory()->create();

        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);

        return $person->fresh();
    }

    /**
     * Привязывает существующее контактное лицо к заданному контрагенту.
     */
    private function attachExistingPersonTo(ContactPerson $person, Contractor $contractor): void
    {
        EmployedPerson::factory()->create([
            'contact_person_id' => $person->id,
            'contractor_id' => $contractor->id,
        ]);
    }

    /**
     * Менеджер видит только своих контрагентов в списке для контактного лица, работающего в нескольких компаниях.
     */
    public function test_manager_index_shows_only_own_contractors_for_shared_person(): void
    {
        $manager = User::factory()->manager()->create();
        $ownContractor = Contractor::factory()->for($manager, 'user')->create(['name' => 'ООО СвойКлиент']);

        $otherManager = User::factory()->manager()->create();
        $otherContractor = Contractor::factory()->for($otherManager, 'user')->create(['name' => 'ООО ЧужойКлиентЗет']);

        $person = $this->personAttachedTo($ownContractor);
        $this->attachExistingPersonTo($person, $otherContractor);

        $this->actingAs($manager)->get(route('contact-people.index'))
            ->assertOk()
            ->assertSee('ООО СвойКлиент')
            ->assertDontSee('ООО ЧужойКлиентЗет');
    }

    /**
     * Админ видит всех контрагентов в списке для контактного лица, работающего в нескольких компаниях.
     */
    public function test_admin_index_shows_all_contractors_for_shared_person(): void
    {
        $admin = User::factory()->admin()->create();

        $manager = User::factory()->manager()->create();
        $ownContractor = Contractor::factory()->for($manager, 'user')->create(['name' => 'ООО СвойКлиент']);

        $otherManager = User::factory()->manager()->create();
        $otherContractor = Contractor::factory()->for($otherManager, 'user')->create(['name' => 'ООО ЧужойКлиентЗет']);

        $person = $this->personAttachedTo($ownContractor);
        $this->attachExistingPersonTo($person, $otherContractor);

        $this->actingAs($admin)->get(route('contact-people.index'))
            ->assertOk()
            ->assertSee('ООО СвойКлиент')
            ->assertSee('ООО ЧужойКлиентЗет');
    }

    /**
     * Создание контактного лица с валидной прошедшей датой рождения сохраняет её в БД.
     */
    public function test_admin_can_store_contact_person_with_valid_birth_date(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $birthDate = now()->subYears(30)->format('Y-m-d');

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Иванов Иван',
                'birth_date' => $birthDate,
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $person = ContactPerson::where('fio', 'Иванов Иван')->first();
        $this->assertNotNull($person);
        $this->assertSame($birthDate, $person->birth_date->format('Y-m-d'));
    }

    /**
     * Поле birth_date опционально: создание без него оставляет NULL (nullable).
     */
    public function test_admin_can_store_contact_person_without_birth_date(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Петров Пётр',
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $person = ContactPerson::where('fio', 'Петров Пётр')->first();
        $this->assertNotNull($person);
        $this->assertNull($person->birth_date);
    }

    /**
     * Валидация: birth_date должна быть корректной датой.
     */
    public function test_store_validates_birth_date_must_be_date(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Сидоров Сидор',
                'birth_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['birth_date']);
    }

    /**
     * Валидация before_or_equal:today: будущая дата рождения (завтра и далее) отклоняется.
     */
    public function test_store_validates_birth_date_must_not_be_in_future(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Фёдоров Фёдор',
                'birth_date' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors(['birth_date' => 'Дата рождения не может быть в будущем.']);
    }

    /**
     * Инвариант ТЗ: сегодняшняя дата рождения разрешена правилом before_or_equal:today.
     */
    public function test_admin_can_store_contact_person_with_birth_date_today(): void
    {
        $admin = User::factory()->admin()->create();
        $contractor = Contractor::factory()->for($admin, 'user')->create();
        $birthDate = now()->format('Y-m-d');

        $this->actingAs($admin)
            ->post(route('contact-people.store', $contractor), [
                'fio' => 'Сегодняшний Сегодням',
                'birth_date' => $birthDate,
            ])
            ->assertRedirect(route('contractors.show', $contractor));

        $person = ContactPerson::where('fio', 'Сегодняшний Сегодням')->first();
        $this->assertNotNull($person);
        $this->assertSame($birthDate, $person->birth_date->format('Y-m-d'));
    }

    /**
     * Обновление сохраняет новое значение birth_date (правило before:today пропускает прошедшую дату).
     */
    public function test_manager_can_update_contact_person_birth_date(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());
        $birthDate = now()->subYears(25)->format('Y-m-d');

        $this->actingAs($manager)
            ->put(route('contact-people.update', $person), [
                'fio' => $person->fio,
                'birth_date' => $birthDate,
            ])
            ->assertRedirect(route('contact-people.show', $person));

        $this->assertSame($birthDate, $person->fresh()->birth_date->format('Y-m-d'));
    }

    /**
     * Валидация update: birth_date должна быть корректной датой (правило идентично create).
     */
    public function test_update_validates_birth_date_must_be_date(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)
            ->put(route('contact-people.update', $person), [
                'fio' => $person->fio,
                'birth_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['birth_date']);
    }

    /**
     * Валидация update: будущая дата рождения отклоняется (before_or_equal:today).
     */
    public function test_update_validates_birth_date_must_not_be_in_future(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());

        $this->actingAs($manager)
            ->put(route('contact-people.update', $person), [
                'fio' => $person->fio,
                'birth_date' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors(['birth_date' => 'Дата рождения не может быть в будущем.']);
    }

    /**
     * Карточка contact-people.show отображает birth_date в формате d.m.Y.
     */
    public function test_show_displays_birth_date_formatted(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());
        $person->update(['birth_date' => now()->subYears(30)]);

        $this->actingAs($manager)
            ->get(route('contact-people.show', $person))
            ->assertOk()
            ->assertSee($person->fresh()->birth_date->format('d.m.Y'));
    }

    /**
     * Карточка show рендерится без 500 и показывает «—» для NULL birth_date
     * (защита регресса: каст 'date' в модели + null-safe ->format() в view).
     */
    public function test_show_displays_dash_when_birth_date_is_null(): void
    {
        $manager = User::factory()->manager()->create();
        $person = $this->personAttachedTo(Contractor::factory()->for($manager, 'user')->create());
        $person->update(['birth_date' => null]);

        $this->actingAs($manager)
            ->get(route('contact-people.show', $person->fresh()))
            ->assertOk()
            ->assertSee('—');
    }
}
