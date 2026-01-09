<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Statut et Priorité
            $table->enum('status', ['requested', 'planned', 'in_progress', 'on_hold', 'completed', 'canceled'])->default('requested');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('work_order_type', ['corrective', 'preventive', 'inspection', 'installation', 'other'])->default('corrective');

            // Relations
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('requester_id')->nullable()->constrained('users')->onDelete('set null');
            $table->nullableMorphs('assignable'); // Pour assigner à un User (technicien) ou une Team

            // Origine de l'ordre de travail
            $table->foreignId('intervention_request_id')->nullable()->constrained('intervention_requests')->onDelete('set null');
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained('maintenance_schedules')->onDelete('set null');

            // Dates
            $table->timestamp('requested_date')->nullable();
            $table->timestamp('planned_start_date')->nullable();
            $table->timestamp('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();

            // Coûts et Durée
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->unsignedInteger('estimated_hours')->nullable()->comment('Durée estimée en heures');
            $table->unsignedInteger('actual_hours')->nullable()->comment('Durée réelle en heures');

            // Détails de complétion
            $table->text('completion_notes')->nullable();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
