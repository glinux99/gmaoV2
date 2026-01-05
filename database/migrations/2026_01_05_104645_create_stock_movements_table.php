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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Utilisation de colonnes polymorphes pour lier le mouvement
            // soit à une SparePart, soit à un Equipment (compteur, clavier, etc.)
            $table->morphs('movable'); // Crée movable_id (BIGINT) et movable_type (VARCHAR)

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->integer('quantity')->default(1);

            // Pour les transferts, on peut utiliser ces champs pour l'origine et la destination
            // On lie directement aux régions pour plus de cohérence
            $table->foreignId('source_region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('destination_region_id')->nullable()->constrained('regions')->onDelete('set null');

            // Ajout de la colonne pour lier à un mouvement parent
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->onDelete('set null'); // Personne responsable du mouvement
            $table->foreignId('intended_for_user_id')->nullable()->constrained('users')->onDelete('set null'); // Personne à qui le stock est destiné (utilisateur final)
            $table->foreignId('parent_movement_id')->nullable()->constrained('stock_movements')->onDelete('set null');

            $table->text('notes')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
