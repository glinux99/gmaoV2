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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->onDelete('set null');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
            // Assumant une future table 'suppliers' pour les fournisseurs/prestataires
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('reference')->nullable()->comment('Référence de la commande ou du devis');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'in_progress', 'completed', 'invoiced', 'paid'])->default('draft');
            $table->decimal('cost', 10, 2)->nullable()->comment('Coût de la prestation');
            $table->date('order_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
