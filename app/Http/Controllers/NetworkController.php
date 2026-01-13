<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Equipment;
use App\Models\EquipmentCharacteristic;
use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class NetworkController extends Controller
{
    /**
     * Affiche la liste ET le dernier réseau actif
     */
    public function index()
    {
        // On récupère tous les réseaux pour la liste latérale
        $networks = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels', 'region'])->latest()->get();

        // On récupère le dernier réseau modifié pour servir de "initialNetwork" par défaut
        $lastNetwork = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->latest()->first();

        return Inertia::render('Actifs/Networks', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType', 'characteristics')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::with('zones')->get(),
            'zones' => Zone::all(),

        ]);
    }

    /**
     * Interface de l'Éditeur pour un réseau spécifique
     */
       public function create(Network $network)
    {
           $networks = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->latest()->get();

        // On récupère le dernier réseau modifié pour servir de "initialNetwork" par défaut
        $lastNetwork = Network::with([
            'nodes.equipment.equipmentType',
            // MODIFICATION : On ne charge que la DERNIÈRE caractéristique pour chaque type.
            'nodes.characteristics' => function ($query) {
                $query->whereIn('id', function ($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('node_equip_specs')
                        ->groupBy('network_node_id', 'equipment_characteristic_id');
                })->with('equipmentCharacteristic'); // On charge les détails de la caractéristique
            },
            'connections',
            'labels'
        ])->where('id', $network->id)->latest()->first();

        return Inertia::render('Actifs/NetworkStudio', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType', 'characteristics')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::with('zones')->get(),
            'zones' => Zone::all(),
            // On charge les caractéristiques par ID d'équipement pour un accès facile en front-end
            'equipmentCharacteristics' => Equipment::with('characteristics')->get()->keyBy('id')->map(function ($equipment) {
                return $equipment->characteristics;
            }),
        ]);
    }
    public function edit(Network $network)
    {
           $networks = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->latest()->get();

        // On récupère le dernier réseau modifié pour servir de "initialNetwork" par défaut
        $lastNetwork = Network::with([
            'nodes.equipment.equipmentType',
            // MODIFICATION : On ne charge que la DERNIÈRE caractéristique pour chaque type.
            'nodes.characteristics' => function ($query) {
                $query->whereIn('id', function ($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('node_equip_specs')
                        ->groupBy('network_node_id', 'equipment_characteristic_id');
                })->with('equipmentCharacteristic'); // On charge les détails de la caractéristique
            },
            'connections',
            'labels'
        ])->where('id', $network->id)->latest()->first();

        return Inertia::render('Actifs/NetworkStudio', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType', 'characteristics')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::with('zones')->get(),
            'zones' => Zone::all(),
            // On charge les caractéristiques par ID d'équipement pour un accès facile en front-end
            'equipmentCharacteristics' => Equipment::with('characteristics')->get()->keyBy('id')->map(function ($equipment) {
                return $equipment->characteristics;
            }),
        ]);
    }

    /**
     * Store : Création d'un nouveau réseau
     */
public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255', // Ex: "Réseau Usine Nord"
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id', // Créateur du plan
            'zoom_level' => 'nullable|numeric',
            'version' => 'nullable|numeric',
            'grid_size' => 'nullable|integer',
            'is_active' => 'boolean',
            'region_id' => 'nullable|exists:regions,id',
            'equipments' => 'nullable|array', // Les équipements sont des noeuds du réseau
            'connections' => 'nullable|array',
            'labels' => 'nullable|array',
             'is_busbar'=> 'nullable|boolean',
        'color' => 'nullable|string',
        ]);

        $network = DB::transaction(function () use ($validated, $request) {
            // 1. Création du réseau
            $network = Network::create([
                'name' => $validated['name'],
                'user_id' => auth()->id() ?? 1,
                'zoom_level' => $validated['zoom_level'] ?? 0.85,
                'version' => $validated['version'] ?? null,
                'description' => $validated['description'] ?? null,
                'grid_size' => $validated['grid_size'] ?? 20,
                'is_active' => $validated['is_active'] ?? true,
                'region_id' => $validated['region_id'] ?? null,
            ]);

            $tempToRealIdMap = [];

            // 2. Création des Noeuds (Equipments)
            foreach ($validated['equipments'] ?? [] as $nodeData) {
                // On cherche l'équipement en bibliothèque ou on le crée si absent
                $equipmentId = $nodeData['libraryId'] ?? null;

                if (!$equipmentId) {
                    $type = EquipmentType::firstOrCreate(['name' => $nodeData['type'] ?? 'Composant']);
                    $equipment = Equipment::firstOrCreate(
                        ['tag' => $nodeData['tag']],
                        [
                            'designation' => $nodeData['designation'],
                            'equipment_type_id' => $type->id,
                            'status' => 'en service',
                            'zone_id' => $nodeData['zone_id'] ?? null,

                        ]
                    );
                    $equipmentId = $equipment->id;
                }

                $node = $network->nodes()->create([
                    'equipment_id' => $equipmentId,
                    'x' => $nodeData['x'],
                    'y' => $nodeData['y'],
                    'w' => $nodeData['w'] ?? 220,
                    'h' => $nodeData['h'] ?? 130,
                     'is_active' => (int) ($nodeData['active'] ==="true" ?? false),
                    'is_root'   => (int) ($nodeData['active'] ==="true" ?? false),
                    'is_busbar' => (bool) ($nodeData['is_busbar'] ?? false), // Ajout de is_busbar
                    'color'     => $nodeData['color'] ?? null, // Ajout de la couleur
 'region_id' => $nodeData['region_id'] ?? null,
 'zone_id' => $nodeData['zone_id'] ?? null,
                ]);

                // 3.1 Sauvegarde des caractéristiques du noeud
                if (isset($nodeData['characteristics']) && is_array($nodeData['characteristics'])) {
                    foreach ($nodeData['characteristics'] as $charData) {
                        $charId = $charData['equipment_characteristic_id'] ?? null;
                        $newValue = $charData['value'] ?? null;
                        $newDate = isset($charData['date']) ? \Carbon\Carbon::parse($charData['date']) : now();

                        // Validation : S'assurer que les IDs sont valides et que la valeur n'est pas nulle.
                        if (!empty($charId) && !empty($equipmentId) && isset($charData['value']) && $charData['value'] !== null) {
                            // On cherche la dernière caractéristique enregistrée pour ce noeud et ce type de caractéristique
                            $latestChar = $node->characteristics()
                                ->where('equipment_characteristic_id', $charId)
                                ->where('equipment_id', $equipmentId)
                                ->latest('date')
                                ->first();

                            // On compare la valeur et la date (jour/mois/année uniquement)
                            $isDifferent = !$latestChar ||
                                           $latestChar->value != $newValue ||
                                           !\Carbon\Carbon::parse($latestChar->date)->isSameDay($newDate);

                            // Si aucune caractéristique n'existe ou si les données sont différentes, on crée un nouvel enregistrement.
                            if ($isDifferent) {
                                $node->characteristics()->create([
                                    'equipment_characteristic_id' => $charId,
                                    'equipment_id' => $equipmentId,
                                    'value' => $newValue,
                                    'date' => $newDate,
                                ]);
                            }
                        }
                    }
                }

                // On stocke la correspondance entre l'ID JS (ex: "root-0") et l'ID SQL (ex: 42)
                $tempToRealIdMap[$nodeData['id']] = $node->id;
            }

            // 3. Création des Connexions avec les nouveaux IDs
            foreach ($validated['connections'] ?? [] as $connData) {
                if (isset($tempToRealIdMap[$connData['fromId']]) && isset($tempToRealIdMap[$connData['toId']])) {
                    $network->connections()->create([
                        'from_node_id' => $tempToRealIdMap[$connData['fromId']],
                        'from_side'    => $connData['fromSide'],
                        'to_node_id'   => $tempToRealIdMap[$connData['toId']],
                        'to_side'      => $connData['toSide'],
                        'color'        => $connData['color'] ?? '#3b82f6',
                        'dash_array'   => $connData['dash'] ?? '0',
                    ]);
                }
            }

            // 4. Création des Labels
            foreach ($validated['labels'] ?? [] as $labelData) {
                $network->labels()->create([
                    'text'      => $labelData['text'],
                    'x'         => $labelData['x'],
                    'y'         => $labelData['y'],
                    'color'     => $labelData['color'] ?? '#94a3b8',
                    'font_size' => $labelData['fontSize'] ?? 14,
                    'is_bold'   => (bool)($labelData['bold'] ?? false),
                    'rotation'  => $labelData['rotation'] ?? 0,
                ]);
            }

            return $network;
        });

        return redirect()->route('networks.index')->with('success', 'Projet enregistré avec succès.');
    }

    /**
     * Update : Reconstruction propre du schéma
     */
public function update(Request $request, Network $network)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'zoom_level' => 'nullable|numeric',
        'region_id' => 'nullable|exists:regions,id',
        'equipments' => 'nullable|array',
        'connections' => 'nullable|array',
        'labels' => 'nullable|array',
        'is_busbar'=> 'nullable|boolean',
        'color' => 'nullable|string',
    ]);

    DB::transaction(function () use ($request, $validated, $network) {
        // 1. Mise à jour du réseau
        $network->update([
            'name' => $validated['name'],
            'zoom_level' => $validated['zoom_level'] ?? $network->zoom_level,
            'region_id' => $validated['region_id'] ?? $network->region_id,
            'description' => $validated['description'] ?? $network->description,
        ]);

        // 2. Gestion des NOEUDS (Equipments)
        $tempToRealIdMap = [];
        $keptNodeIds = [];

        if (!empty($validated['equipments'])) {
            foreach ($validated['equipments'] as $nodeData) {
                // Déterminer l'ID de l'équipement (Library)
                $equipmentId = $nodeData['libraryId'] ?? null;

                // Si c'est un nouveau composant hors bibliothèque
                if (!$equipmentId && isset($nodeData['tag'])) {
                    $type = EquipmentType::firstOrCreate(['name' => $nodeData['type'] ?? 'Composant']);
                    $equipment = Equipment::firstOrCreate(
                        ['tag' => $nodeData['tag']],
                        ['designation' => $nodeData['designation'] ?? 'Sans nom', 'equipment_type_id' => $type->id]
                    );
                    $equipmentId = $equipment->id;
                }


                // UPDATE OR CREATE le noeud
                // On utilise nodeData['id'] seulement s'il est numérique (ID réel SQL)
                $nodeId = isset($nodeData['id']) && is_numeric($nodeData['id']) ? $nodeData['id'] : null;

                $node = $network->nodes()->updateOrCreate(
                    ['id' => $nodeId],
                    [
                        'equipment_id' => $equipmentId,
                        'x' => $nodeData['x'],
                        'y' => $nodeData['y'],
                        'w' => $nodeData['w'] ?? 220,
                        'h' => $nodeData['h'] ?? 130,
                        'is_active' => filter_var($nodeData['active'], FILTER_VALIDATE_BOOLEAN),
                        'is_root' => filter_var($nodeData['is_root'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'is_busbar' => filter_var($nodeData['is_busbar'] ?? false, FILTER_VALIDATE_BOOLEAN), // Ajout de is_busbar
                        'color' => $nodeData['color'] ?? null, // Ajout de la couleur
                        'region_id' => $nodeData['region_id'] ?? null,
                        'zone_id' => $nodeData['zone_id'] ?? null,
                    ]
                );

                $keptNodeIds[] = $node->id;
                $tempToRealIdMap[$nodeData['id']] = $node->id;

                // 3. Gestion des CARACTÉRISTIQUES (Historisation)
                if (!empty($nodeData['characteristics'])) {
                    foreach ($nodeData['characteristics'] as $charData) {
                        $charId = $charData['equipment_characteristic_id'] ?? null;
                        $newValue = $charData['value'] ?? null;
                        $newDate = isset($charData['date']) ? \Carbon\Carbon::parse($charData['date']) : now();

                        if (!$charId || $newValue === null) continue;

                        $latestChar = $node->characteristics()
                            ->where('equipment_characteristic_id', $charId)
                            ->latest('date')->first();

                        // Comparer la valeur ET la date (jour/mois/année uniquement)
                        $isDifferent = !$latestChar ||
                                       $latestChar->value != $newValue ||
                                       !\Carbon\Carbon::parse($latestChar->date)->isSameDay($newDate);

                        if ($isDifferent) {
                            $node->characteristics()->create([
                                'equipment_characteristic_id' => $charId,
                                'equipment_id' => $equipmentId,
                                'value' => $newValue,
                                'date' => $newDate,
                            ]);
                        }
                    }
                }
            }
        }

        // 4. NETTOYAGE : Supprimer les nœuds qui ne sont plus dans le JSON
        $network->nodes()->whereNotIn('id', $keptNodeIds)->delete();

        // 5. RECONSTRUCTION DES CONNECTIONS (Plus simple de Delete/Create pour les liens)
        $network->connections()->delete();
        if (!empty($validated['connections'])) {
            foreach ($validated['connections'] as $connData) {
                $from = $tempToRealIdMap[$connData['fromId']] ?? null;
                $to = $tempToRealIdMap[$connData['toId']] ?? null;

                if ($from && $to) {
                    $network->connections()->create([
                        'from_node_id' => $from,
                        'from_side' => $connData['fromSide'],
                        'to_node_id' => $to,
                        'to_side' => $connData['toSide'],
                        'color' => $connData['color'] ?? '#ef4444',
                    ]);
                }
            }
        }

        // 6. RECONSTRUCTION DES LABELS
        $network->labels()->delete();
        if (!empty($validated['labels'])) {
            foreach ($validated['labels'] as $labelData) {
                $network->labels()->create([
                    'text' => $labelData['text'] ?? '',
                    'x' => $labelData['x'],
                    'y' => $labelData['y'],
                    'color' => $labelData['color'] ?? '#94a3b8',
                ]);
            }
        }
    });

    return back()->with('success', 'Schéma mis à jour (nœuds conservés).');
}
    /**
     * Destroy : Suppression
     */
    public function destroy(Network $network)
    {
        $network->delete();
        return redirect()->route('networks.index')->with('success', 'Réseau supprimé.');
    }
}
