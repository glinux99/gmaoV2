<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('cascade');
            $table->string('frequency'); // Ex: 'quarterly', 'monthly', 'weekly'
            $table->integer('day_of_week')->nullable()->comment('0=Dimanche, 1=Lundi, ...');
            $table->time('execution_time')->nullable()->comment('Heure de la journée pour la maintenance');
            $table->date('next_due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
