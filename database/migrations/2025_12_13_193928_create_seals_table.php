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
        Schema::create('seals', function (Blueprint $table) {
            $table->id(); // Identifiant unique du scellé
            $table->string('serial_number')->unique(); // Numéro de série du scellé
            $table->string('type')->nullable(); // Type de scellé (ex: compteur, boîtier, général)
            $table->string('status')->default('available'); // Statut (ex: disponible, utilisé, endommagé)
            $table->date('installation_date')->nullable(); // Date d'installation du scellé
            $table->foreignId('meter_id')->nullable()->constrained('meters')->onDelete('set null'); // Lien vers le compteur scellé
            $table->foreignId('connection_id')->nullable()->constrained('connections')->onDelete('set null'); // Lien vers le raccordement (pour les boîtiers ou scellés généraux)
            $table->text('notes')->nullable(); // Notes additionnelles

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seals');
    }
};
