<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создаёт таблицу ролей и наполняет её начальными значениями:
     * 1 — Менеджер (роль по умолчанию), 2 — Администратор.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['id' => 1, 'slug' => 'manager', 'title' => 'Менеджер', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'slug' => 'admin', 'title' => 'Администратор', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
