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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // L'utilisateur qui demande le congé
            $table->string('type'); // Type de congé (ex: "annuel", "maladie", "maternité", "paternité", "sans solde", "autre")
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable(); // Raison de la demande de congé
            $table->enum('status', ['pending', 'approved', 'rejected', 'canceled'])->default('pending'); // Statut de la demande
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a approuvé/rejeté
            $table->date('approval_date')->nullable();
            $table->string('document_path')->nullable(); // Chemin vers le document justificatif (certificat médical, etc.)
            $table->text('notes')->nullable(); // Notes internes

            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
