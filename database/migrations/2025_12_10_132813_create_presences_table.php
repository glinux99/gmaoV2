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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->enum('status', ['present', 'absent', 'on_leave', 'holiday'])->default('present');
            $table->text('notes')->nullable();
            // Champ pour lier à une fiche de prestation (ServiceOrder) si applicable
            $table->foreignId('service_order_id')->nullable()->constrained('service_orders')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
