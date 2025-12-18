<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->nullable();
            $table->json('characteristics')->nullable();
            $table->string('designation')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            // Quantité uniquement suivie pour le statut "en stock"; par défaut 1
            $table->unsignedInteger('quantity')->default(1);
            $table->string('price')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->enum('status', ['en service', 'en panne', 'en maintenance', 'hors service', 'en stock'])->default('en service');
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->double('purchase_price')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->foreignId('equipment_type_id')->nullable()->constrained('equipment_types')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
