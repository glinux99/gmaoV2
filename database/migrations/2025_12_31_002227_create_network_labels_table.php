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
        Schema::create('network_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->onDelete('cascade');
            $table->string('text');
            $table->integer('font_size')->default(14);
            $table->string('color')->default('#94a3b8');
            $table->boolean('is_bold')->default(false);
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_labels');
    }
};
