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
        Schema::table('connections', function (Blueprint $table) {
            $table->foreignId('meter_id')->nullable()->constrained('meters')->onDelete('set null'); // Lien vers le compteur
            $table->foreignId('keypad_id')->nullable()->constrained('keypads')->onDelete('set null'); // Lien vers le clavier
            $table->string('cable_section')->nullable(); // Section câble de raccordement (ex: 2x16 mm²)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            //
        });
    }
};
