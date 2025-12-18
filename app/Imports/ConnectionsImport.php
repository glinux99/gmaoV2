<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\Zone;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Connection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Recommandé pour utiliser les noms de colonnes
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ConnectionsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Fonction utilitaire pour les booléens (gère "Yes", "No", "1", etc.)
        $convertToBool = function ($value) {
            $str = strtolower(trim((string)($value ?? '')));
            return in_array($str, ['oui', 'yes', 'vrai', '1', 'true']);
        };

        // --- 1. Gestion des Modèles Liés ---

        // Région : colonne "Région"
        $region = Region::firstOrCreate(['designation' => $row['region'] ?? 'Inconnue']);

        // Zone : colonne "Zone"
        $zone = Zone::firstOrCreate(
            ['title' => $row['zone'] ?? 'Inconnue'],
            ['region_id' => $region->id]
        );

        // Compteur : colonne "Numéro du compteur"
        $meter = null;
        if (!empty($row['numero_du_compteur'])) {
            $meter = Meter::firstOrCreate(['serial_number' => $row['numero_du_compteur']]);
        }

        // Clavier : colonne "Numéro du clavier"
        $keypad = null;
        if (!empty($row['numero_du_clavier'])) {
            $keypad = Keypad::firstOrCreate(['serial_number' => $row['numero_du_clavier']]);
        }

        // --- 2. Création de la Connexion ---
        // Les clés du tableau $row correspondent aux en-têtes transformés en "slug" par Laravel Excel
        return new Connection([
            'customer_code'          => $row['code_client_automatique'] ?? null,
            'region_id'              => $region->id,
            'zone_id'                => $zone->id,
            'status'                 => $row['statut'] ?? 'pending',
            'first_name'             => $row['prenom'] ?? null,
            'last_name'              => $row['nom_de_famille'] ?? null,
            'phone_number'           => $row['telephone'] ?? null,
            'secondary_phone_number' => $row['telephone_2'] ?? null,
            'gps_latitude'           => $this->convertDMSToDecimal($row['coordonnees_gps_lattitude'] ?? null),
            'gps_longitude'          => $this->convertDMSToDecimal($row['coordonnees_gps_longitude'] ?? $row['coordonees_gps_longitude'] ?? null),
            'customer_type'          => $row['type_de_client'] ?? null,
            'customer_type_details'  => $row['type_de_client_details'] ?? null,
            'commercial_agent_name'  => $row['nom_de_lagent_commercial'] ?? null,
            'amount_paid'            => $row['montant_paye'] ?? 0,
            'payment_number'         => $row['numero_paiement'] ?? null,
            'payment_voucher_number' => $row['numero_du_bon_de_paiement'] ?? null,
            'rccm_number'            => $row['numero_rccm'] ?? null,

            // Dates (Vérifie si c'est un format Excel ou une string Y-m-d)
            'payment_date'           => $this->transformDate($row['date_de_paiement'] ?? null),
            'connection_date'        => $this->transformDate($row['date_de_raccordement'] ?? null),

            'connection_type'        => $row['type_de_raccordement_compteur'] ?? null,
            'meter_id'               => $meter?->id,
            'keypad_id'              => $keypad?->id,
            'cable_section'          => $row['section_cable_de_raccordement'] ?? null,
            'meter_type_connected'   => $row['type_de_compteur_raccorde'] ?? null,
            'cable_length'           => $row['longueur_cable_de_raccordementold'] ?? null,
            'box_type'               => $row['type_de_boitier'] ?? null,
            'meter_seal_number'      => $row['numero_de_scelle_compteur'] ?? null,
            'box_seal_number'        => $row['numero_de_scelle_boite'] ?? null,
            'phase_number'           => $row['numero_de_phase'] ?? null,
            'amperage'               => $row['amperage'] ?? null,
            'voltage'                => $row['voltage'] ?? null,
            'pole_number'            => $row['numero_du_poteau'] ?? null,
            'distance_to_pole'       => $row['distance_entre_la_maison_et_le_poteau_bt'] ?? null,

            // Exemples de booléens
            'is_verified'            => $convertToBool($row['verifie'] ?? null),
            'with_ready_box'         => $convertToBool($row['maison_avec_ready_box'] ?? null),
        ]);
    }

    /**
     * Transforme les dates mixtes (Excel ou String)
     */
    private function transformDate($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convertit les coordonnées GPS du format Degrés, Minutes, Secondes (DMS) en format décimal.
     * Gère les formats comme "29°21'49.36" ou "29.3654".
     */
    private function convertDMSToDecimal($dmsString)
    {
        if (empty($dmsString)) {
            return null;
        }

        // Supprime les espaces et remplace la virgule par un point pour les décimales
        $dmsString = str_replace(',', '.', trim($dmsString));

        // Vérifie si c'est déjà un format décimal simple
        if (is_numeric($dmsString)) {
            return (float) $dmsString;
        }

        // Expression régulière pour capturer les degrés, minutes et secondes
        // Ex: 29°21'49.36" ou 29°21'49.36
        if (preg_match('/^(\d+)[°](\d+)\'(\d+(?:\.\d+)?)"?$/', $dmsString, $matches)) {
            $degrees = (float) $matches[1];
            $minutes = (float) $matches[2];
            $seconds = (float) $matches[3];
            return $degrees + ($minutes / 60) + ($seconds / 3600);
        }

        return null; // Retourne null si le format n'est pas reconnu
    }
}
