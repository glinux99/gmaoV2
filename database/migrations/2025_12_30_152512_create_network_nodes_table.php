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
    $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
    $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
    $table->string('status')->nullable()->default('en service');
    // État spécifique au réseau (peut différer de l'état réel de l'équipement)
    $table->integer('is_active')->default(1);
    $table->integer('is_root')->default(0); // Source d'énergie (G)

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
