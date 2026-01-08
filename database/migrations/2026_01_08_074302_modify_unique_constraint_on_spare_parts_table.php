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
        Schema::table('spare_parts', function (Blueprint $table) {
            // 1. Supprimer l'ancienne contrainte unique sur 'reference'
            // Le nom 'spare_parts_reference_unique' est une convention Laravel,
            // vérifiez le nom exact dans votre base de données si cela échoue.
            $table->dropUnique('spare_parts_reference_unique');

            // 2. Ajouter la nouvelle contrainte unique composite
            $table->unique(['reference', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spare_parts', function (Blueprint $table) {
            // Inverser les opérations en cas de rollback
            $table->dropUnique(['reference', 'region_id']);
            $table->unique('reference', 'spare_parts_reference_unique');
        });
    }
};
