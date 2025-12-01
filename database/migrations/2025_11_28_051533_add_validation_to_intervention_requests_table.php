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
        Schema::table('intervention_requests', function (Blueprint $table) {
            $table->foreignId('validator_id')->nullable()->constrained('users')->onDelete('set null')->after('priority');
            $table->timestamp('validated_at')->nullable()->after('validator_id');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_requests', function (Blueprint $table) {
            $table->dropForeign(['validator_id']);
            $table->dropColumn(['validator_id', 'validated_at', 'validation_notes']);
        });
    }
};
