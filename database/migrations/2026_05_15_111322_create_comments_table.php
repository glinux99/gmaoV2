<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();           // Nom saisi par le visiteur
            $table->string('author_email')->nullable();          // Email saisi
            $table->text('content');
            $table->boolean('is_approved')->default(true);       // Modération
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index(['post_id', 'is_approved']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
