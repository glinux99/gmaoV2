<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Equipment;
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
            'library' => Equipment::with('equipmentType')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::all(),
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
        $lastNetwork = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->where('id', $network->id)->latest()->first();

        return Inertia::render('Actifs/NetworkStudio', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::all(),
            'zones' => Zone::all(),
        ]);
    }
    public function edit(Network $network)
    {
           $networks = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->latest()->get();

        // On récupère le dernier réseau modifié pour servir de "initialNetwork" par défaut
        $lastNetwork = Network::with(['nodes.equipment.equipmentType', 'connections', 'labels'])->where('id', $network->id)->latest()->first();

        return Inertia::render('Actifs/NetworkStudio', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            }),
            'regions' => Region::all(),
            'zones' => Zone::all(),
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
 'region_id' => $nodeData['region_id'] ?? null,
 'zone_id' => $nodeData['zone_id'] ?? null,
                ]);

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
        'name' => 'required|string|max:255', // Ex: "Réseau Usine Nord"
        'description' => 'nullable|string',
        'user_id' => 'nullable|exists:users,id', // Créateur du plan
        'zoom_level' => 'nullable|numeric',
        'version' => 'nullable|numeric',
        'grid_size' => 'nullable|integer',
        'is_active' => 'boolean',
        'region_id' => 'nullable|exists:regions,id',
        'equipments' => 'nullable|array',
        'connections' => 'nullable|array',
        'labels' => 'nullable|array',
    ]);

    DB::transaction(function () use ($validated, $network) {
        // 1. Mise à jour des infos de base
        $network->update([
            'name' => $validated['name'],
            'zoom_level' => $validated['zoom_level'] ?? $network->zoom_level,
            'version' => $validated['version'] ?? $network->version,
            'region_id' => $validated['region_id'] ?? $network->region_id,
            'description' => $validated['description'] ?? $network->description,
            'grid_size' => $validated['grid_size'] ?? $network->grid_size,
            'is_active' => $validated['is_active'] ?? $network->is_active,

        ]);

        // 2. Nettoyage complet
        // Note: Assurez-vous que les relations sont bien définies dans le modèle Network.php
        $network->connections()->delete();
        $network->nodes()->delete();
        $network->labels()->delete();

        $tempToRealIdMap = [];

        // 3. Recréer les Noeuds (Equipments)
        if (!empty($validated['equipments'])) {
            foreach ($validated['equipments'] as $nodeData) {
                $equipmentId = $nodeData['libraryId'] ?? null;

                if (!$equipmentId) {
                    $type = EquipmentType::firstOrCreate(['name' => $nodeData['type'] ?? 'Composant']);
                    $equipment = Equipment::firstOrCreate(
                        ['tag' => $nodeData['tag']],
                        [
                            'designation' => $nodeData['designation'] ?? 'Sans nom',
                            'equipment_type_id' => $type->id,
                            'status' => 'en service'
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
                    'region_id' => $nodeData['region_id'] ?? null,
                    'zone_id' => $nodeData['zone_id'] ?? null,
                ]);

                // On stocke la correspondance entre l'ID temporaire du front-end et le nouvel ID SQL
                $tempToRealIdMap[$nodeData['id']] = $node->id;
            }
        }

        // 4. Recréer les Connections
        if (!empty($validated['connections'])) {
            foreach ($validated['connections'] as $connData) {
                if (isset($tempToRealIdMap[$connData['fromId']]) && isset($tempToRealIdMap[$connData['toId']])) {
                    $network->connections()->create([
                        'from_node_id' => $tempToRealIdMap[$connData['fromId']],
                        'from_side'    => $connData['fromSide'],
                        'to_node_id'   => $tempToRealIdMap[$connData['toId']],
                        'to_side'      => $connData['toSide'],
                        'color'        => $connData['color'] ?? '#ef4444', // Rouge par défaut selon votre UI
                        'dash_array'   => $connData['dash'] ?? '0',
                    ]);
                }
            }
        }

        // 5. Recréer les Labels
        if (!empty($validated['labels'])) {
            foreach ($validated['labels'] as $labelData) {
                $network->labels()->create([
                    'text' => $labelData['text'] ?? '',
                    'x' => $labelData['x'],
                    'y' => $labelData['y'],
                    'color' => $labelData['color'] ?? '#94a3b8',
                    'font_size' => $labelData['fontSize'] ?? 14,
                    'is_bold' => (bool)($labelData['bold'] ?? false),
                    'rotation' => $labelData['rotation'] ?? 0,
                ]);
            }
        }
    });

    return back()->with('success', 'Schéma réseau mis à jour avec succès.');
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
