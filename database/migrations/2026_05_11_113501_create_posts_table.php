<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique(); // Permalien unique

            // Contenu
            $table->longText('content')->nullable(); // HTML de l'éditeur riche
            $table->text('excerpt')->nullable(); // Résumé
            $table->string('cover_image')->nullable(); // URL de l'image

            // État et Visibilité
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false); // À la une
            $table->dateTime('published_at')->nullable(); // Date de publication / planification

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // Statistiques
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);

            // Clés étrangères (Relations)
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Corbeille : Permet de restaurer un article supprimé
        });

        // Table Pivot pour la relation Many-to-Many avec les Tags (Mots-clés)
        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('posts');
    }
};
