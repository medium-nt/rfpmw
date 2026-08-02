<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет необязательную привязку запроса/КП к проекту контрагента.
     * nullOnDelete: при force-delete проекта связь обнуляется (мягкое удаление не затрагивается).
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('comment')->constrained()->nullOnDelete();
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('comment')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
