<?php

namespace App\Imports;

use App\Models\SparePart;
use App\Models\Label;
use App\Models\Unity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SparePartImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Laravel Excel transforme les titres en "slugs" (minuscules, sans accents)
        // "Nom d'affichage" devient 'nom_daffichage'
        // "Coût" devient 'cout'
        // "Unité de mesure" devient 'unite_de_mesure'

        $nomAffichage = $row['nom_daffichage'] ?? null;

        if (!$nomAffichage) {
            return null;
        }

        // Extraction de la référence entre [] et de la désignation
        // Exemple : "[ACC-0014] Casque" -> $matches[1] = ACC-0014, $matches[2] = Casque
        if (preg_match('/\[(.*?)\]\s*(.*)/', $nomAffichage, $matches)) {
            $reference = $matches[1];
            $designation = trim($matches[2]);
        } else {
            // Si le format est invalide, on peut décider de sauter la ligne
            return null;
        }

        // 1. Gérer le Label (Désignation)
        $label = Label::firstOrCreate([
            'designation' => Str::limit($designation, 255)
        ]);

        // 2. Gérer l'Unité
        $unity = null;
        if (!empty($row['unite_de_mesure'])) {
            $unity = Unity::firstOrCreate([
                'designation' => $row['unite_de_mesure']
            ]);
        }

        // 3. Nettoyer le prix (Coût)
        $rawPrice = $row['cout'] ?? 0;
        $cleanPrice = str_replace([' ', ','], ['', '.'], $rawPrice);
        $price = (float) $cleanPrice;

        // 4. Créer ou mettre à jour la pièce détachée
        return SparePart::updateOrCreate(
            ['reference' => $reference],
            [
                'label_id'     => $label->id,
                'price'        => $price,
                'unity_id'     => $unity->id ?? null,
                'quantity'     => $row['quantite'] ?? 0, // Ajustez selon vos colonnes réelles
                'user_id'      => Auth::id(),
                'updated_at'   => now(),
            ]
        );
    }
}
