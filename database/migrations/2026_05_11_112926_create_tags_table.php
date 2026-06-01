<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Création de la table des mots-clés (Tags)
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true); // Ajouté pour être cohérent avec Categories
            $table->timestamps();
            $table->softDeletes(); // Ajouté pour être cohérent avec Categories
        });

        // 2. Création de la table pivot (Liaison entre les articles et les tags)
        // La convention Laravel veut que le nom soit post_tag (ordre alphabétique au singulier)

    }

    public function down(): void
    {

        Schema::dropIfExists('tags');
    }
};
