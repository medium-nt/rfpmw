<?php

namespace Database\Seeders;

use App\Models\Contractor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демо-наполнение таблицы контрагентов: добавляет 12 записей разных типов за менеджером.
 */
class ContractorSeeder extends Seeder
{
    /**
     * Создаёт 12 контрагентов (по 3 каждого типа), закрепляя их за менеджером 2@2.ru.
     */
    public function run(): void
    {
        $managerId = User::where('email', '2@2.ru')->value('id');

        if ($managerId === null) {
            $this->command->error('Менеджер 2@2.ru не найден — сначала запустите DatabaseSeeder.');

            return;
        }

        Contractor::factory()
            ->count(12)
            ->sequence(
                ['type' => 'customer'],
                ['type' => 'partner'],
                ['type' => 'supplier'],
                ['type' => 'vendor'],
            )
            // user_id переопределяет фабричный User::factory() — лишние пользователи не создаются.
            ->create(['user_id' => $managerId]);

        $this->command->info('Создано 12 контрагентов.');
    }
}
