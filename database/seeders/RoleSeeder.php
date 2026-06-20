<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Заполняет таблицу ролей начальными значениями (idempotent).
     *
     * Синхронизирует справочник ролей с миграцией: 1 — Менеджер, 2 — Администратор.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['id' => 1, 'slug' => 'manager'],
            ['title' => 'Менеджер'],
        );

        Role::firstOrCreate(
            ['id' => 2, 'slug' => 'admin'],
            ['title' => 'Администратор'],
        );
    }
}
