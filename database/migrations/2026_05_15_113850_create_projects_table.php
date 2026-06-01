<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('province_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0); // en pourcentage (0-100)
            $table->integer('order')->default(0);
            $table->string('status')->default('in_progress');
            $table->text('description')->nullable();
            $table->text('target')->nullable(); // objectif
            $table->string('lead')->nullable(); // responsable
            $table->string('budget')->nullable(); // ex: "185 000 USD"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
              $table->string('slug')->nullable()->unique(); // Permalien unique
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
