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
            $table->enum('status', [
                Task::STATUS_PLANNED,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
                Task::STATUS_CANCELLED,
                Task::STATUS_LATE,
                Task::STATUS_STARTED_LATE,
            ])->default(Task::STATUS_PLANNED);

            $table->enum('priority', ['Basse', 'Moyenne', 'Haute', 'Critique'])->default('Moyenne');
            $table->enum('maintenance_type', ['Préventive', 'Corrective', 'Améliorative'])->nullable();

            $table->timestamp('planned_start_date')->nullable();
            $table->timestamp('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();

            $table->integer('time_spent')->nullable()->comment('Temps passé en minutes');

            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');

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
