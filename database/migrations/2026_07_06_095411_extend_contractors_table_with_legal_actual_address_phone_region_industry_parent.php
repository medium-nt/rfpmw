<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Расширение карточки контрагента: переименование address → legal_address
     * и добавление новых полей (фактический адрес, телефон, регион, отрасль, головной контрагент).
     */
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->renameColumn('address', 'legal_address');
        });

        Schema::table('contractors', function (Blueprint $table) {
            // Увеличиваем длину юридического адреса под валидацию max:500 (было VARCHAR 255).
            $table->string('legal_address', 500)->nullable()->change();

            $table->string('actual_address', 500)->nullable()->after('legal_address');
            $table->string('phone', 255)->nullable()->after('actual_address');
            $table->string('region', 255)->nullable()->after('phone');
            $table->string('industry', 255)->nullable()->after('region');

            // Самоссылающийся FK на головного контрагента.
            $table->foreignId('parent_id')
                ->nullable()
                ->after('industry')
                ->constrained('contractors')
                ->nullOnDelete();
        });
    }

    /**
     * Откат: возврат к исходной схеме с единственной колонкой address.
     */
    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'industry', 'region', 'phone', 'actual_address']);
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->renameColumn('legal_address', 'address');
            $table->string('address', 255)->nullable()->change();
        });
    }
};
