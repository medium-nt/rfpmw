<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Добавляет обязательное уникальное поле username (логин) и делает email
     * необязательным — аутентификация переведена с email на username.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->after('name')->nullable();
            $table->string('email')->nullable()->change();
        });

        // Backfill: username = очищенная часть email до @; при совпадении дописываем _id.
        $taken = [];
        foreach (DB::table('users')->whereNull('username')->get() as $user) {
            $email = $user->email ?? '';
            $base = $email !== '' ? Str::before($email, '@') : '';
            $base = preg_replace('/[^a-zA-Z0-9_-]/', '', $base);

            if ($base === '') {
                $base = 'user';
            }

            $username = $base;
            if (in_array($username, $taken, true) || DB::table('users')->where('username', $username)->exists()) {
                $username = $base.'_'.$user->id;
            }

            $taken[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable(false)->unique()->change();
        });
    }

    /**
     * Удаляет поле username и возвращает email в NOT NULL.
     * Пустые email заполняются как <username>@test.ru — иначе NOT NULL нарушится.
     */
    public function down(): void
    {
        // Заполняем пустые email значением <username>@test.ru перед возвратом в NOT NULL.
        foreach (DB::table('users')->whereNull('email')->get() as $user) {
            $login = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($user->username ?? ''));

            if ($login === '') {
                $login = 'user_'.$user->id;
            }

            DB::table('users')->where('id', $user->id)->update(['email' => $login.'@test.ru']);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });
    }
};
