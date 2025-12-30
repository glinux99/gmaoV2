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
        Schema::create('networks', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Ex: "Réseau Usine Nord"
    $table->text('description')->nullable();
    $table->foreignId('user_id')->constrained(); // Créateur du plan
    $table->float('zoom_level')->default(0.85);
    $table->integer('grid_size')->default(20);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
