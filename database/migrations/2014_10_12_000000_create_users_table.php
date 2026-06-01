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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Clé étrangère vers la table 'teams' (Départements)
            // nullable() permet d'avoir un utilisateur sans équipe
            // nullOnDelete() permet de garder l'utilisateur si on supprime son équipe
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();

            // Identité (Remplace le champ 'name')
            $table->string('name');
            $table->string('last_name')->nullable();

            // Authentification (Conservation de votre logique)
            $table->string('email', 250)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            // Conservation de votre champ spécifique
            $table->double('hourly_rate')->nullable();

            // Champs Profil & Ressources Humaines (Nouveaux)
            $table->string('position')->nullable();             // Poste (ex: Dev Senior)
            $table->string('phone', 50)->nullable();            // Téléphone
            $table->string('contract_type', 50)->nullable();    // Type de contrat (CDI, CDD...)
            $table->date('hiring_date')->nullable();            // Date d'embauche
            $table->string('linkedin_url')->nullable();         // URL LinkedIn
            $table->text('bio')->nullable();                    // Biographie / Notes
            $table->string('avatar')->nullable();               // Chemin de l'image de profil
            $table->boolean('is_active')->default(true);        // Statut du compte (Actif/Inactif)

            $table->rememberToken();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
