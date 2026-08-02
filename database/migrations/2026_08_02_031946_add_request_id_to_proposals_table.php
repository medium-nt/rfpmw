<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // Привязка КП к исходному запросу (опциональна). Паттерн проекта — как nullable FK в events.
            $table->foreignId('request_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->after('user_id');

            // Один запрос → не более одного КП. NULL-значения (КП без запроса) дубликатами не считаются.
            $table->unique('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique(['request_id']);
            $table->dropConstrainedForeignId('request_id');
        });
    }
};
