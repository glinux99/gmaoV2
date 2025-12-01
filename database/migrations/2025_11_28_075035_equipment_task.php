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
            Schema::create('equipment_task', function (Blueprint $table) {

            // 1. Clé étrangère vers la table 'equipment'
            $table->foreignId('equipment_id')
                  ->constrained('equipment') // Assurez-vous que le nom de la table est 'equipment' comme dans votre migration
                  ->onDelete('cascade');

            // 2. Clé étrangère vers la table 'maintenances'
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->onDelete('cascade');

            // 3. Définir les deux colonnes comme clé primaire composite pour assurer l'unicité
            $table->primary(['equipment_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('equipment_task');
    }
};
