<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('maintenances', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->nullableMorphs('assignable'); // Crée assignable_id et assignable_type
    $table->string('type')->nullable();
    $table->string('status')->nullable()->default('Planifiée');
    $table->string('priority')->nullable()->default('Moyenne');
    $table->dateTime('scheduled_start_date')->nullable();
    $table->dateTime('scheduled_end_date')->nullable();
    $table->integer('estimated_duration')->nullable(); // en minutes
    $table->dateTime('started_at')->nullable(); // Date et heure de début réelle
    $table->dateTime('completed_at')->nullable(); // Date et heure de fin réelle
    $table->decimal('cost', 10, 2)->nullable();
    $table->foreignId('network_id')->nullable()->constrained('networks')->onDelete('set null');
    $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');

    // Champs de récurrence
    $table->string('recurrence_type')->nullable();
    $table->integer('recurrence_interval')->nullable();
    $table->integer('recurrence_month_interval')->nullable();
    $table->json('recurrence_days')->nullable();
    $table->foreignId('network_node_id')->nullable()->constrained('network_nodes')->onDelete('set null');
    $table->integer('recurrence_day_of_month')->nullable();
    $table->integer('recurrence_month')->nullable();
    $table->integer('reminder_days')->nullable();
    $table->text('custom_recurrence_config')->nullable();
    $table->foreignId('maintenance_schedule_id')->nullable()->constrained('maintenance_schedules')->onDelete('set null');
    $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('set null');
    $table->foreignId('intervention_request_id')->nullable()->constrained('intervention_requests')->onDelete('set null');

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
