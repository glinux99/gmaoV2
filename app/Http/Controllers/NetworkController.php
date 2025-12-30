<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Equipment;
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
        $networks = Network::withCount(['nodes'])->latest()->get();

        // On récupère le dernier réseau modifié pour servir de "initialNetwork" par défaut
        $lastNetwork = Network::with(['nodes.equipment.equipmentType', 'connections'])->latest()->first();

        return Inertia::render('Actifs/Networks', [
            'networks' => $networks,
            'initialNetwork' => $lastNetwork,
            'library' => Equipment::with('equipmentType')->get()->groupBy(function($item) {
                return $item->equipmentType->name;
            })
        ]);
    }

    /**
     * Interface de l'Éditeur pour un réseau spécifique
     */
    public function edit(Network $network)
    {
        return Inertia::render('Actifs/Networks/Editor', [
            'initialNetwork' => $network->load(['nodes.equipment.type', 'connections']),
            'library' => Equipment::with('equipmentType')->get()->groupBy(function($item) {
                return $item->type->category;
            })
        ]);
    }

    /**
     * Store : Création d'un nouveau réseau
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'equipments' => 'nullable|array',
            'connections' => 'nullable|array',
            'labels' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $network = Network::create([
                'name' => $validated['name'],
                'user_id' => auth()->id() ?? 1,
                'zoom_level' => $request->zoom_level ?? 0.85,
                'version' => $validated['version'] ?? null,
                'date' => $validated['date'] ?? null,
            ]);

            $tempToRealIdMap = [];
            foreach ($validated['equipments'] ?? [] as $nodeData) {
                $node = $network->nodes()->create([
                    'equipment_id' => $nodeData['tag'], // Assuming 'tag' from frontend maps to equipment_id
                    'x' => $nodeData['x'],
                    'y' => $nodeData['y'],
                    'w' => $nodeData['w'] ?? 200,
                    'h' => $nodeData['h'] ?? 100,
                    'is_active' => $nodeData['active'] ?? true,
                    'is_root' => $nodeData['isRoot'] ?? false,
                ]);
                $tempToRealIdMap[$nodeData['id']] = $node->id;
            }

            // Connections and Labels can be handled similarly if needed, using $tempToRealIdMap for node IDs
        });
        return redirect()->route('networks.index')
            ->with('success', 'Nouveau réseau initialisé.');
    }

    /**
     * Update : Sauvegarde du Canvas (Noeuds + Connections)
     */
    public function update(Request $request, Network $network)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'equipments' => 'present|array', // Renamed from 'nodes' to 'equipments' to match frontend
            'connections' => 'present|array',
            'labels' => 'present|array',
            'zoom_level' => 'required|numeric',
        ]);

        try {
            DB::transaction(function () use ($request, $network) {
                $network->update([
                    'name' => $request->name,
                    'version' => $request->version ?? null,
                    'date' => $request->date ?? null,
                    'zoom_level' => $request->zoom_level
                ]);

                // Flush (Nettoyage) pour reconstruction propre
                $network->connections()->delete();
                $network->nodes()->delete();
                // Assuming labels are stored in a polymorphic relation or a dedicated table
                // $network->labels()->delete(); // Uncomment if labels are stored this way

                $tempToRealIdMap = [];

                // 1. Reconstruction des Noeuds
                foreach ($request->equipments as $nodeData) { // Use 'equipments' from frontend
                    // Find the actual equipment_id from the 'tag' or 'designation'
                    $equipment = Equipment::where('tag', $nodeData['tag'])
                                            ->orWhere('designation', $nodeData['designation'])
                                            ->first();
                    if (!$equipment) {
                        // Handle case where equipment is not found, maybe create it or throw an error
                        // For now, let's assume it must exist and use a fallback or skip
                        continue;
                    }

                    $node = $network->nodes()->create([
                        'equipment_id' => $equipment->id, // Use the actual equipment ID
                        'x'            => $nodeData['x'],
                        'y'            => $nodeData['y'],
                        'w'            => $nodeData['w'] ?? 200,
                        'h'            => $nodeData['h'] ?? 100,
                        'is_active'    => $nodeData['active'] ?? true,
                        'is_root'      => $nodeData['isRoot'] ?? false,
                    ]);
                    $tempToRealIdMap[$nodeData['id']] = $node->id;
                }

                // 2. Reconstruction des Connections
                foreach ($request->connections as $connData) {
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

                // 3. Reconstruction des Labels (assuming a 'network_labels' table or similar)
                foreach ($request->labels ?? [] as $labelData) {
                    // Assuming a 'labels' relation on the Network model
                    $network->labels()->create([
                        'text' => $labelData['text'],
                        'font_size' => $labelData['fontSize'],
                        'color' => $labelData['color'],
                        'x' => $labelData['x'],
                        'y' => $labelData['y'],
                    ]);
                }
            });

            return back()->with('success', 'Modifications enregistrées.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
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
