<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        Schema::create('intervention_requests', function (Blueprint $table) {
            $table->id();

            // Informations de base de la demande
            $table->string('title');
            $table->text('description')->nullable();

            // Relation polymorphe pour le demandeur
            // Crée 'requested_by_id' (integer) et 'requested_by_type' (string)
            $table->morphs('requested_by');

            // Détails de la localisation et statut
            $table->text('location_details')->nullable();
            $table->string('status')->default('Ouverte'); // Ex: Ouverte, En cours, Fermée
            $table->string('priority')->default('Moyenne'); // Ex: Faible, Moyenne, Urgent

            // Dates
            $table->dateTime('reported_at')->nullable(); // Date/heure de signalement
            $table->dateTime('closed_at')->nullable(); // Date/heure de résolution

            // Clés étrangères
            $table->foreignId('region_id')
                  ->nullable()
                  ->constrained('regions') // Assurez-vous que la table 'regions' existe
                  ->onDelete('set null');

            // Vous pourriez ajouter ici une relation pour l'équipement spécifique si nécessaire
            // $table->foreignId('equipment_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervention_requests');
    }
};
