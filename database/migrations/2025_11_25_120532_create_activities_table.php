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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->onDelete('cascade');
            $table->foreignId('intervention_request_id')->nullable()->constrained('intervention_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Informations confirmées de la tâche
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('activities')->onDelete('cascade'); // ID de l'activité parente
            $table->nullableMorphs('assignable'); // Crée assignable_id et assignable_type
             $table->integer('jobber')->nullable(); // Nombre de techniciens
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null'); // Ajout de la région
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null'); // Ajout de la zone

            // Pièces détachées
            $table->json('spare_parts_used')->nullable(); // Pièces détachées utilisées (JSON pour stocker id, quantité)
            $table->json('spare_parts_returned')->nullable(); // Pièces détachées retournées (JSON pour stocker id, quantité)

            // État de la tâche
            $table->string('status')->nullable()->default('in_progress'); // Ex: 'in_progress', 'completed', 'suspended', 'canceled'
            $table->string('title')->nullable(); // Ex: 'in_progress', 'completed', 'suspended', 'canceled'
            $table->text('problem_resolution_description')->nullable(); // Description de la résolution du problème
            $table->text('proposals')->nullable(); // Propositions ou recommandations
            $table->json('additional_information')->nullable(); // Informations additionnelles génériques

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
