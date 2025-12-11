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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Lien vers l'utilisateur
            $table->string('employee_id')->unique()->nullable(); // Numéro d'employé unique
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->date('hire_date'); // Date d'embauche
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('employment_status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('notes')->nullable();

            // Documents de l'employé (peut être géré via une relation polymorphique ou une table séparée si complexe)
            $table->string('cv_path')->nullable();
            $table->string('id_card_path')->nullable();
            $table->string('contract_path')->nullable();
            $table->json('other_documents')->nullable(); // Pour stocker des chemins vers d'autres documents

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
