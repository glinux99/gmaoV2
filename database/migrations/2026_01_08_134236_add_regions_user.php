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
        Schema::table('users', function (Blueprint $table) {
            // Supprime l'ancienne colonne 'region' si elle existe et n'est pas une clé étrangère
            if (Schema::hasColumn('users', 'region')) {
                $table->dropColumn('region');
            }
            // Ajoute la nouvelle colonne 'region_id' comme clé étrangère
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
