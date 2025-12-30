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
        Schema::create('network_connections', function (Blueprint $table) {
           $table->id();
    $table->foreignId('network_id')->constrained()->onDelete('cascade');

    // Source de la liaison
    $table->foreignId('from_node_id')->constrained('network_nodes')->onDelete('cascade');
    $table->enum('from_side', ['N', 'S', 'E', 'W']);

    // Destination de la liaison
    $table->foreignId('to_node_id')->constrained('network_nodes')->onDelete('cascade');
    $table->enum('to_side', ['N', 'S', 'E', 'W']);

    // Propriétés visuelles du câble
    $table->string('color')->default('#3b82f6');
    $table->string('dash_array')->default('0'); // Pour différencier Phase/Terre

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_connections');
    }
};
