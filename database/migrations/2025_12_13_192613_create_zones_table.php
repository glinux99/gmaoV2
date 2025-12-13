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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Titre ou nom de la zone (ex: GOM-Z024a/BIRANDA)
            $table->text('description')->nullable(); // Description de la zone
            $table->string('nomenclature')->nullable(); // Nomenclature de la zone (ex: Z024a)
            $table->string('number')->nullable(); // Numéro de la zone (ex: 024a)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
