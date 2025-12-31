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
    Schema::create('equipment_maintenance', function (Blueprint $table) {
        $table->id(); // Garder un ID unique simple est plus flexible

        // 1. Clé étrangère vers 'equipment'
        $table->foreignId('equipment_id')
              ->constrained('equipment')
              ->onDelete('cascade');

        // 2. Clé étrangère vers 'maintenances'
        $table->foreignId('maintenance_id')
              ->constrained('maintenances')
              ->onDelete('cascade');

        // 3. Clé étrangère vers 'network_nodes'
        $table->foreignId('network_node_id')
              ->constrained('network_nodes')
              ->onDelete('cascade');

        // L'UNICITÉ : Empêche d'avoir le même équipement, pour la même maintenance, sur le même nœud
        $table->unique(['equipment_id', 'maintenance_id', 'network_node_id'], 'eq_maint_node_unique');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance');
    }
};
