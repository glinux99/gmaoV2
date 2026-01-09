<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('network_id')->nullable()->constrained('networks')->onDelete('set null');
            $table->foreignId('network_node_id')->nullable()->constrained('network_nodes')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('intervention_request_id')->nullable()->constrained('intervention_requests')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['network_id']);
            $table->dropColumn('network_id');
            $table->dropForeign(['network_node_id']);
            $table->dropColumn('network_node_id');
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
            $table->dropForeign(['intervention_request_id']);
            $table->dropColumn('intervention_request_id');
        });
    }
};
