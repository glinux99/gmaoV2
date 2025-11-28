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
        Schema::create('spare_part_characteristics', function (Blueprint $table) {
            $table->id();

            // Foreign key for the spare part
            $table->foreignId('spare_part_id')->constrained('spare_parts')->onDelete('cascade');

            // Foreign key for the characteristic definition
            $table->foreignId('label_characteristic_id')->constrained('label_characteristics')->onDelete('cascade');

            $table->string('value'); // The actual value for this specific spare part's characteristic
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_characteristics');
    }
};
