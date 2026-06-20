<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin')]
#[Description('Создать первого пользователя-администратора с интерактивным вводом данных')]
class CreateAdminCommand extends Command
{
    /**
     * Execute the console command.
     *
     * Запрашивает имя, email и пароль администратора через Laravel Prompts,
     * валидирует данные и создаёт пользователя. Пароль хешируется автоматически
     * через cast `hashed` модели User.
     */
    public function handle(): int
    {
        $name = text(
            label: 'Имя администратора',
            placeholder: 'Например: Иван Администраторов',
            required: 'Имя обязательно для заполнения',
        );

        $email = text(
            label: 'Email',
            placeholder: 'admin@example.com',
            required: 'Email обязателен для заполнения',
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Введите корректный email',
                User::where('email', $value)->exists() => 'Пользователь с таким email уже существует',
                default => null,
            },
        );

        $password = password(
            label: 'Пароль',
            required: 'Пароль обязателен для заполнения',
            validate: fn (string $value) => strlen($value) < 8
                ? 'Пароль должен быть не менее 8 символов'
                : null,
        );

        $adminRole = Role::where('slug', 'admin')->first();

        if (! $adminRole) {
            $this->error('Роль администратора не найдена. Сначала выполните сидер: php artisan db:seed --class=RoleSeeder');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $adminRole->id,
        ]);

        info("Администратор «{$user->name}» ({$user->email}) успешно создан с ID {$user->id}.");

        return self::SUCCESS;
    }
}
