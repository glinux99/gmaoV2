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
        Schema::create('intervention_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->nullable()->default('pending');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // User who created the request (e.g., an admin or technician)
            $table->foreignId('requested_by_connection_id')->nullable()->constrained('connections')->onDelete('set null'); // Connection (client) who made the request
            $table->nullableMorphs('assignable'); // Assignable to User or Team
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->enum('intervention_reason', [
                'Dépannage Réseau Urgent',
                'Réparation Éclairage Public',
                'Entretien Réseau Planifié',
                'Incident Majeur Réseau',
                'Support Achat MobileMoney',
                'Support Achat Token Impossible',
                'Aide Recharge (Sans clavier)',
                'Élagage Réseau',
                'Réparation Chute de Tension',
                'Coupure Individuelle (CI)',
                'CI Équipement Client',
                'CI Équipement Virunga',
                'CI Vol de Câble',
                'Dépannage Clavier Client',
                'Réparation Compteur Limité',
                'Rétablissement Déconnexion',
                'Déplacement Câble (Planifié)',
                'Déplacement Poteau (Planifié)',
                'Reconnexion Client',
                'Support Envoi Formulaire',
                'Intervention Non-Classifiée',
            ])->nullable();
            $table->string('category')->nullable(); // e.g., 'Réseau', 'Support Technique', 'Client', 'Support / Autres'
            $table->enum('technical_complexity', ['Pas complexe', 'Peu complexe', 'Moyennement complexe', 'Très complexe'])->nullable();
            $table->integer('min_time_hours')->nullable();
            $table->integer('max_time_hours')->nullable();
            $table->text('comments')->nullable();
            $table->string('priority')->nullable(); // This seems redundant with technical_complexity and intervention_reason, but keeping it as per original
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('completed_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervention_requests');
    }
};
