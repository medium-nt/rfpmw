<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Учётные записи для первого входа.
        User::factory()->admin()->create([
            'name' => 'Админ',
            'email' => '1@1.ru',
            'password' => '111111',
        ]);

        User::factory()->manager()->create([
            'name' => 'Менеджер',
            'email' => '2@2.ru',
            'password' => '222222',
        ]);

        $this->call(ContractorSeeder::class);
    }
}
