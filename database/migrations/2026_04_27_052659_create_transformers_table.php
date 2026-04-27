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
        Schema::create('transformers', function (Blueprint $table) {
            $table->id();

            // Identification & Indexation
            $table->string('transformer_id')->index()->comment('Identifiant unique interne ou code asset');
            $table->uuid('uuid')->unique()->nullable(); // Pour des APIs sécurisées

            // Données Temporelles (Crucial pour l'IoT/Sensing)
            $table->timestamp('measured_at')->index(); // Plus explicite que 'timestamp'

            // États et Alarmes (Regroupés pour la clarté)
            $table->boolean('temperature_alarm')->default(false);
            $table->boolean('pressure_alarm')->default(false);
            $table->boolean('oil_level_alarm')->default(false);
            $table->boolean('dmcr_alarm')->default(false);
            $table->boolean('dmcr_trip')->default(false);

            // Mesures Analogiques avec Précision
            // On utilise decimal(8,2) pour permettre des températures ou charges plus élevées si besoin
            $table->decimal('load_percentage', 5, 2)->nullable();
            $table->decimal('oil_temperature', 5, 2)->nullable();
            $table->decimal('ambient_temperature', 5, 2)->nullable();

            // Relations
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('set null');
            $table->foreignId('network_node_id')->nullable()->constrained('network_nodes')->onDelete('set null');

            // Statut via Enum pour restreindre les valeurs possibles
            $table->enum('status', ['operational', 'maintenance', 'alert', 'offline'])->nullable()
                  ->default('operational');

            // Metadata additionnelle (JSON est parfait pour les spécificités fabriquant)
            $table->json('metadata')->nullable()->comment('Détails techniques : marque, modèle, version firmware');

            // Maintenance & Traçabilité
            $table->timestamps();
            $table->softDeletes(); // Pour ne pas perdre l'historique en cas de suppression

            // Index composite pour les requêtes de monitoring fréquentes
            $table->index(['transformer_id', 'measured_at']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transformers');
    }
};
