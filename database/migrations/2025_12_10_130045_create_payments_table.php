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
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // Identifiant unique du paiement
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'other'])->default('bank_transfer');
            $table->string('reference')->nullable(); // Numéro de transaction, chèque, etc.
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null'); // Utilisateur ayant enregistré le paiement
            $table->nullableMorphs('payable'); // Relation polymorphique pour lier à Employee, Supplier, etc.
            $table->string('category')->default('salary'); // 'salary', 'bonus', 'expense_reimbursement', 'advance', 'other'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
