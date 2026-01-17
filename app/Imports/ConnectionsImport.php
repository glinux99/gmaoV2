<?php

namespace App\Imports;

use App\Models\Region;
use App\Models\Zone;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Connection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Recommandé pour utiliser les noms de colonnes
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ConnectionsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Nettoyage et correction de l'encodage pour chaque champ
        // --- AJOUT POUR DÉBOGAGE ---
        // dd($row); // Affiche les données brutes avant nettoyage

        $cleanedRow = collect($row)->map(fn($value) => is_string($value) ? $this->fixEncoding($value) : $value)->all();
        // dd($cleanedRow);
        // Fonction utilitaire pour les booléens (gère "Yes", "No", "1", etc.)
        $convertToBool = function ($value) {
            $str = strtolower(trim((string)($value ?? '')));
            return in_array($str, ['oui', 'yes', 'vrai', '1', 'true']);
        };

        $firstName = str_replace(',', '', $cleanedRow['noms_du_client'] ?? null);
        if (empty($firstName)) {
            // Si le prénom/nom est vide, on ignore la ligne.
            return null;
        }

        // --- 1. Gestion des Modèles Liés ---

        // Région : colonne "Région"
        $regionName = $cleanedRow['region'] ?? 'Inconnue';
        $region = Region::where('designation', 'LIKE', "%{$regionName}%")
    ->orWhere('code', 'LIKE', "%{$regionName}%")
    ->firstOr(function () use ($regionName) {
        return Region::create([
            'designation' => $regionName,
            'code'        => str()->slug($regionName), // Utilise un slug comme code
        ]);
    });
        // Zone : colonne "Zone"
        $zone = Zone::firstOrCreate( // La zone est souvent liée à la région
            ['title' => $cleanedRow['zone'] ?? 'Inconnue', 'region_id' => $region->id],
            ['region_id' => $region->id]
        );

        // Compteur : colonne "Numéro du compteur"
        $meter = null;
        if (!empty($cleanedRow['numero_de_serie_du_compteur'])) {
            $meter = Meter::updateOrCreate(
                ['serial_number' => $cleanedRow['numero_de_serie_du_compteur']],
                [
                    'region_id' => $region->id,
                    'status' => 'active',
                    'type' => 'prepaid',
                    'manufacturer' => $cleanedRow['type_de_raccordement'] ?? null,
                    'model' => $cleanedRow['type_de_raccordement'] ?? null,
                ]
            );
        }

        // Clavier : colonne "Numéro du clavier"
        $keypad = null;
        if (!empty($cleanedRow['numero_de_serie_du_clavier'])) {
            $keypad = Keypad::updateOrCreate(
                ['serial_number' => $cleanedRow['numero_de_serie_du_clavier']],
                [
                    'region_id' => $region->id, // Assigner la région à la création
                    'meter_id' => $meter?->id,
                    'manufacturer' => $cleanedRow['type_de_raccordement'] ?? null,
                    'model' => $cleanedRow['type_de_raccordement'] ?? null,
                    'type' => 'standard',
                    'status' => 'installed',
                    'installation_date' => $this->transformDate($cleanedRow['type_de_raccordement'] ?? null),
                ]);
        }

        // Le code client est maintenant la clé de référence
        $customerCode = $cleanedRow['numero_du_client'] ?? 'VE-CLI-' . mt_rand(100000, 999999);

        // Si le code client est vide, on ignore la ligne pour éviter une erreur SQL.
        if (empty($customerCode)) {
            return null;
        }

        // --- 2. Création de la Connexion ---
        // Les clés du tableau $row correspondent aux en-têtes transformés en "slug" par Laravel Excel
        // Utilisation de updateOrCreate pour éviter les doublons sur 'customer_code'
        $connection = Connection::updateOrCreate(
            ['customer_code' => $customerCode], // Clé unique pour la recherche
            [
            'customer_code'          => $customerCode,
            'region_id'              => $region->id,
            'zone_id'                => $zone->id,
            'status'                 => $cleanedRow['statut'] ?? 'pending',
            'first_name'             => $firstName, // Le champ "noms_du_client" contient le nom complet
            'last_name'              => "", // Le nom de famille n'est pas séparé dans votre exemple
            'phone_number'           => $cleanedRow['telephone'] ?? null, // Assurez-vous que cette colonne existe
            'secondary_phone_number' => $cleanedRow['telephone_2'] ?? null, // Assurez-vous que cette colonne existe
            'gps_latitude'           => $this->convertDMSToDecimal($cleanedRow['coordonees_gps'] ?? null),
            'gps_longitude'          => $this->convertDMSToDecimal($cleanedRow['coordonees_gps_longitude'] ?? null),
            'customer_type'          => $cleanedRow['type_de_client'] ?? null,
            'customer_type_details'  => $cleanedRow['type_de_client_details'] ?? null,
            'commercial_agent_name'  => $cleanedRow['nom_de_lagent_commercial'] ?? null,
            'amount_paid'            => $cleanedRow['montant_paye'] ?? 0,
            'payment_number'         => $cleanedRow['numero_paiement'] ?? null,
            'payment_voucher_number' => $cleanedRow['numero_du_bon_de_paiement'] ?? null,
            'rccm_number'            => $cleanedRow['numero_rccm'] ?? null,

            // Dates (Vérifie si c'est un format Excel ou une string Y-m-d)
            'payment_date'           => $this->transformDate($cleanedRow['date_de_paiement'] ?? null),
            'connection_date'        => $this->transformDate($cleanedRow['date_de_raccordement'] ?? null),

            'connection_type'        => $cleanedRow['type_de_raccordement'] ?? null,
            'meter_id'               => $meter?->id,
            'keypad_id'              => $keypad?->id,
            'cable_section'          => $cleanedRow['section_cacble_de_raccordement'] ?? null,
            'meter_type_connected'   => $cleanedRow['type_de_compteur_raccorde'] ?? null,
            'cable_length'           => (int)($cleanedRow['longueur_du_cacble_de_raccordement'] ?? 0),
            'box_type'               => $cleanedRow['type_de_boite'] ?? null,
            'meter_seal_number'      => $cleanedRow['numero_de_scelle_compteur'] ?? null,
            'box_seal_number'        => $cleanedRow['numero_de_scelle_boite'] ?? null,
            'phase_number'           => $cleanedRow['numero_de_phase'] ?? null,
            'amperage'               => $cleanedRow['amperage_a'] ?? null,
            'voltage'                => (int)($cleanedRow['voltage_v'] ?? 0),
            'pole_number'            => $cleanedRow['numero_de_poteau'] ?? null,
            'distance_to_pole'       => $cleanedRow['distance_entre_la_maison_et_le_poteau_bt'] ?? null,

            // Exemples de booléens
            'is_verified'            => $convertToBool($cleanedRow['verifie'] ?? null),
            'with_ready_box'         => $convertToBool($cleanedRow['maison_avec_ready_box'] ?? null),
            ]
        );

        // --- 3. Synchronisation post-création/mise à jour ---
        // Assure que les équipements liés héritent des bonnes informations du raccordement.
        if ($meter) {
            $meter->update([
                'connection_id' => $connection->id,
                'region_id' => $connection->region_id,
                'zone_id' => $connection->zone_id,
            ]);
        }
        if ($keypad) {
            $keypad->update([
                'connection_id' => $connection->id,
                'region_id' => $connection->region_id,
                'zone_id' => $connection->zone_id,
                'meter_id' => $connection->meter_id, // Assure que le clavier est lié au même compteur que la connexion
            ]);
        }

        return $connection;

    }

    public function rules(): array
    {
        return [
            'numero_du_client' => 'required|string',
            'noms_du_client' => 'required|string',
            'region' => 'required|string',
        ];
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

    private function fixEncoding($text)
    {
        // Tente de convertir de ISO-8859-1 (souvent la source des problèmes) vers UTF-8
        return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
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

        // Si c'est déjà un nombre, on le retourne directement
        if (is_numeric($dmsString)) {
            return (float) $dmsString;
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
