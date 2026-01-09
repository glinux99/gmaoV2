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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('intervention_request_id')->nullable()->constrained('intervention_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null'); // Ajout de la région
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null'); // Ajout de la zone
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['intervention_request_id']);
            $table->dropColumn('intervention_request_id');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
