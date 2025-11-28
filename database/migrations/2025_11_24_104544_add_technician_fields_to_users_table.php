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
        Schema::table('users', function (Blueprint $table) {
            $table->string('fonction')->nullable();
            $table->string('numero')->nullable();
            $table->string('region')->nullable();
            $table->string('pointure')->nullable(); // Shoe size
            $table->string('size')->nullable();
            $table->string('profile_photo')->nullable();
               // Clothing size
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fonction', 'numero', 'region', 'pointure', 'size','profile_photo']);
        });
    }
};
