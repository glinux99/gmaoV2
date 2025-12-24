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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User who generated the report
            $table->string('report_type'); // e.g., 'daily', 'weekly', 'monthly', 'custom'
            $table->json('parameters')->nullable(); // JSON to store report generation parameters (e.g., date range, filters)
            $table->string('status')->default('generated'); // e.g., 'generated', 'pending', 'failed'
            $table->string('file_path')->nullable(); // Path to the generated report file (e.g., PDF, CSV)
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // If the report is scheduled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
