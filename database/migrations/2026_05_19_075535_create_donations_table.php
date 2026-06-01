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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('type')->default('once'); // 'once' or 'monthly'
            $table->date('donation_date')->nullable();
            $table->boolean('contacted')->default(false);
            $table->boolean('tax_receipt')->default(false);
            $table->boolean('newsletter')->default(false);
            $table->text('notes')->nullable();
            $table->integer('order')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->ipAddress()->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
