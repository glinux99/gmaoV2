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
        Schema::create('network_nodes', function (Blueprint $table) {
            $table->id();
    $table->foreignId('network_id')->constrained()->onDelete('cascade');
    $table->foreignId('equipment_id')->constrained()->onDelete('cascade');

    // État spécifique au réseau (peut différer de l'état réel de l'équipement)
    $table->boolean('is_active')->default(true);
    $table->boolean('is_root')->default(false); // Source d'énergie (G)

    // Géométrie pour le Canvas Vue
    $table->integer('x')->default(100);
    $table->integer('y')->default(100);
    $table->integer('w')->default(200);
    $table->integer('h')->default(100);

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_nodes');
    }
};
