<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;

    private static $catalog = [
        ['name' => 'Poste de Transformation HT/BT', 'prefix' => 'G', 'icon' => 'pi-building', 'category' => 'Source'],
        ['name' => 'Groupe Électrogène de Secours', 'prefix' => 'G', 'icon' => 'pi-sync', 'category' => 'Source'],
        ['name' => 'Onduleur Central (UPS)', 'prefix' => 'G', 'icon' => 'pi-database', 'category' => 'Source'],
        ['name' => 'Champ Photovoltaïque', 'prefix' => 'G', 'icon' => 'pi-sun', 'category' => 'EnR'],
        ['name' => 'Éolienne Urbaine', 'prefix' => 'G', 'icon' => 'pi-map', 'category' => 'EnR'],
        ['name' => 'Système de Stockage Hydrogène', 'prefix' => 'G', 'icon' => 'pi-filter-fill', 'category' => 'Stockage'],
        ['name' => 'Disjoncteur de Tête (TGBT)', 'prefix' => 'Q', 'icon' => 'pi-lock', 'category' => 'Protection'],
        ['name' => 'Interrupteur Différentiel 30mA', 'prefix' => 'Q', 'icon' => 'pi-shield', 'category' => 'Protection'],
        ['name' => 'Parafoudre Type 2', 'prefix' => 'Q', 'icon' => 'pi-cloud-download', 'category' => 'Protection'],
        ['name' => 'Sectionneur à Coupure Visible', 'prefix' => 'Q', 'icon' => 'pi-external-link', 'category' => 'Coupure'],
        ['name' => 'Relais de Protection Homopolaire', 'prefix' => 'Q', 'icon' => 'pi-info-circle', 'category' => 'Protection'],
        ['name' => 'Transformateur d\'Isolement', 'prefix' => 'T', 'icon' => 'pi-sort-alt', 'category' => 'Transformateur'],
        ['name' => 'Redresseur Industriel AC/DC', 'prefix' => 'T', 'icon' => 'pi-chevron-right', 'category' => 'Conversion'],
        ['name' => 'Variateur de Fréquence (VFD)', 'prefix' => 'T', 'icon' => 'pi-sliders-h', 'category' => 'Commande'],
        ['name' => 'Chargeur DC Rapide', 'prefix' => 'T', 'icon' => 'pi-bolt', 'category' => 'Conversion'],
        ['name' => 'Armoire de Distribution Divisionnaire', 'prefix' => 'A', 'icon' => 'pi-list', 'category' => 'Distribution'],
        ['name' => 'Gaine à Jeu de Barres', 'prefix' => 'A', 'icon' => 'pi-ellipsis-v', 'category' => 'Distribution'],
        ['name' => 'Coffret de Chantier Connecté', 'prefix' => 'A', 'icon' => 'pi-briefcase', 'category' => 'Distribution'],
        ['name' => 'Moteur Asynchrone IE4', 'prefix' => 'M', 'icon' => 'pi-cog', 'category' => 'Moteur'],
        ['name' => 'Pompe à Chaleur Air/Eau', 'prefix' => 'M', 'icon' => 'pi-home', 'category' => 'Thermique'],
        ['name' => 'Borne de Recharge Véhicule Électrique', 'prefix' => 'E', 'icon' => 'pi-car', 'category' => 'Mobilité'],
        ['name' => 'Système d\'Éclairage DALI', 'prefix' => 'E', 'icon' => 'pi-eye', 'category' => 'Eclairage'],
        ['name' => 'Centrale de Traitement d\'Air (CTA)', 'prefix' => 'M', 'icon' => 'pi-cloud', 'category' => 'HVAC'],
        ['name' => 'Compteur d\'Énergie Communicant', 'prefix' => 'P', 'icon' => 'pi-chart-bar', 'category' => 'Mesure'],
        ['name' => 'Analyseur de Qualité Réseau', 'prefix' => 'P', 'icon' => 'pi-search-plus', 'category' => 'Mesure'],
        ['name' => 'Automate Programmable (API)', 'prefix' => 'K', 'icon' => 'pi-box', 'category' => 'Automatisme'],
        ['name' => 'Passerelle IoT / Cloud', 'prefix' => 'K', 'icon' => 'pi-share-alt', 'category' => 'Smart-Grid'],
    ];

    private static $index = 0;

    /**
     * Permet de récupérer la taille du catalogue depuis le Seeder
     */
    public static function getCatalogCount(): int
    {
        return count(self::$catalog);
    }

    public function definition(): array
    {
        // On récupère l'élément à l'index actuel
        $item = self::$catalog[self::$index % count(self::$catalog)];

        // On incrémente pour le prochain appel
        self::$index++;

        return [
            'name' => $item['name'],
            'prefix' => $item['prefix'],
            'icon' => $item['icon'],
            'category' => $item['category'],
            'metadata' => json_encode([
                'voltage_range' => $this->faker->randomElement(['230V', '400V', '20kV']),
                'maintenance_interval' => $this->faker->numberBetween(6, 24) . ' mois'
            ])
        ];
    }
}
