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
        Schema::table('engins', function (Blueprint $table) {
            // Ajoute la colonne region_id qui peut être nulle
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('engins', function (Blueprint $table) {
            // Supprime la contrainte de clé étrangère et la colonne
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
