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
        Schema::create('node_equip_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->constrained('network_nodes')->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
            $table->foreignId('equipment_characteristic_id')->constrained('equipment_characteristics')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_node_equipment_characterstics');
    }
};
