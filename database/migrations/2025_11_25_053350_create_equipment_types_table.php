<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix')->nullable(); // e.g., 'G', 'Q', 'T', 'A', 'M', 'E', 'P', 'K'
            $table->string('icon')->nullable(); // e.g., 'pi-building', 'pi-sync'
            $table->string('category')->nullable(); // e.g., 'Source', 'Protection'
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional flexible data
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_types');
    }
};
