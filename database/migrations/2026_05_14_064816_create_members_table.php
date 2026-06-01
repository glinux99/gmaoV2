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
        Schema::create('members', function (Blueprint $table) {
    $table->id();
    // Clé étrangère vers l'équipe
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('position'); // Rôle / Poste (ex: Directeur, Développeur...)
    $table->string('email')->unique()->nullable();
    $table->string('phone')->nullable();
    $table->string('avatar')->nullable(); // Chemin de l'image de profil
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
