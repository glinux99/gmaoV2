<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Task;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->nullableMorphs('assignable'); // Crée assignable_id et assignable_type
            $table->string('priority')->nullable();
            $table->string('maintenance_type')->nullable();
            $table->string('requester_department')->nullable();
            $table->string('department')->nullable();
            $table->string('recurrence_interval')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->string('estimated_cost')->nullable();
            $table->timestamp('planned_start_date')->nullable();
            $table->timestamp('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();

            $table->integer('time_spent')->nullable()->comment('Temps passé en minutes');
            $table->integer('jobber')->nullable(); // Nombre de techniciens
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Ajout de la clé étrangère pour l'utilisateur
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
