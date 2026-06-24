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
        Schema::create('employed_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['contact_person_id', 'contractor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employed_people');
    }
};
