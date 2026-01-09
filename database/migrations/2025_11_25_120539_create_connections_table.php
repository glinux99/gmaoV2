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
        Schema::create('connections', function (Blueprint $table) {
            $table->id(); // N°
            $table->string('customer_code')->unique(); // Code Client automatique (ex: VE-CLI-118094)
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null'); // Région (ex: Goma)
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null'); // Zone (ex: GOM-Z024a/BIRANDA)
            $table->string('status')->default('pending'); // Statut (ex: 5 - Raccordé)
            $table->string('first_name'); // Prénom
            $table->string('last_name')->nullable(); // Nom de famille
            $table->string('phone_number')->nullable(); // Téléphone
            $table->string('secondary_phone_number')->nullable(); // Télephone 2
            $table->decimal('gps_latitude', 10, 7)->nullable(); // Coordonnées GPS Lattitude
            $table->decimal('gps_longitude', 10, 7)->nullable(); // Coordonées GPS Longitude
            $table->string('customer_type')->nullable(); // Type de Client (ex: MENAGE)
            $table->string('customer_type_details')->nullable(); // Type de Client-Détails (ex: Simple)
            $table->string('commercial_agent_name')->nullable(); // Nom de l'Agent Commercial

            // --- Détails du Paiement ---
            $table->decimal('amount_paid', 10, 2)->nullable(); // Montant Payé
            $table->string('payment_number')->nullable(); // Numéro Paiement (ex: BEC 1749)
            $table->string('payment_voucher_number')->nullable(); // Numéro du bon de paiement (ex: I-118094)
            $table->date('payment_date')->nullable(); // Date de paiement
            $table->boolean('is_verified')->default(false); // Vérifié?

            // --- Détails Techniques du Raccordement ---
            $table->string('connection_type')->nullable(); // Type de raccordement
            $table->date('connection_date')->nullable(); // Date de raccordement

            $table->string('meter_type_connected')->nullable(); // Type de compteur raccordé (ex: E460 1ph)
            $table->integer('cable_length')->nullable(); // Longueur câble de raccordement
            $table->string('box_type')->nullable(); // Type de boîtier (ex: Boite double)
            $table->string('meter_seal_number')->nullable(); // Numéro de scellé compteur
            $table->string('box_seal_number')->nullable(); // Numéro de scellé boîte
            $table->string('phase_number')->nullable(); // Numéro de phase (ex: S)
            $table->string('amperage')->nullable(); // Amperage (ex: 40 A)
            $table->integer('voltage')->nullable(); // Voltage (ex: 223)
            $table->boolean('with_ready_box')->default(false); // Maison avec Ready Box?
            $table->string('tariff')->nullable(); // Tarif
            $table->string('tariff_index')->nullable(); // Tarif Index

            // --- Poteaux ---
            $table->string('pole_number')->nullable(); // Numéro du poteau
            $table->string('distance_to_pole')->nullable(); // Distance entre la maison et le poteau BT
            $table->boolean('needs_small_pole')->default(false); // Besoin d'un petit poteau?
            $table->integer('bt_poles_installed')->nullable(); // Poteaux BT installés
            $table->integer('small_poles_installed')->nullable(); // Petits poteaux installés

            // --- Compteurs Supplémentaires ---
            $table->string('additional_meter_1')->nullable(); // Numéro compteur supplémentaire 1
            $table->string('additional_meter_2')->nullable(); // Numéro compteur supplémentaire 2
            $table->string('additional_meter_3')->nullable(); // Numéro compteur supplémentaire 3

            // --- Informations diverses ---
            $table->string('rccm_number')->nullable(); // Numéro RCCM
            $table->json('materials_used')->nullable(); // materiels utilisee

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
