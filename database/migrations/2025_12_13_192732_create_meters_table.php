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
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique(); // Numéro de série du compteur
            $table->string('model')->nullable(); // Modèle du compteur
            $table->string('manufacturer')->nullable(); // Fabricant du compteur
            $table->string('type')->nullable(); // Type de compteur (ex: monophasé, triphasé, prépaiement)
            $table->string('status')->default('available'); // Statut (ex: disponible, installé, en panne)
            $table->date('installation_date')->nullable(); // Date d'installation
            $table->foreignId('connection_id')->nullable()->constrained('connections')->onDelete('set null'); // Lien vers le raccordement
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->boolean('is_additional')->default(false); // Indique si c'est un compteur additionnel pour une connexion
            $table->text('notes')->nullable(); // Notes additionnelles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
