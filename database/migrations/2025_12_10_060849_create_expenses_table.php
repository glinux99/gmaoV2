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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->enum('category', ['parts', 'labor', 'travel', 'external_service', 'other'])->default('other');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Qui a enregistré la dépense
            $table->nullableMorphs('expensable'); // Pour lier à une tâche, une maintenance, un équipement, etc.
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable(); // Chemin vers le justificatif/reçu
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
