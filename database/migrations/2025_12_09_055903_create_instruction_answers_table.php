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
        Schema::create('instruction_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->onDelete('cascade');

            $table->foreignId('task_instruction_id')->nullable()->constrained('task_instructions')->onDelete('cascade');
            $table->foreignId(' maintenance_instruction_id')->nullable()->constrained('maintenance_instructions')->onDelete('cascade');
            $table->foreignId('activity_instruction_id')->nullable()->constrained('activity_instructions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('value')->nullable();
            $table->timestamps();

            // Assurer qu'il ne peut y avoir qu'une seule réponse par instruction pour une activité donnée.
            // Le nom de la contrainte est généré automatiquement, mais vous pouvez le spécifier :
            // $table->unique(['activity_id', 'task_instruction_id'], 'activity_instruction_answer_unique');
            // $table->unique(['activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instruction_answers');
    }
};
