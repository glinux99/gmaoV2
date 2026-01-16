<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Zone::with('region');

        // Gestion des filtres
        $filters = $request->input('filters', []);

        if (!empty($filters)) {
            // Filtre global
            if (isset($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('title', 'like', "%{$globalFilter}%")
                      ->orWhere('nomenclature', 'like', "%{$globalFilter}%")
                      ->orWhereHas('region', fn($subQ) => $subQ->where('designation', 'like', "%{$globalFilter}%"));
                });
            }

            // Filtres par colonne
            $this->applyColumnFilter($query, $filters, 'nomenclature');
            $this->applyColumnFilter($query, $filters, 'title');

            // Filtre pour la colonne 'region.designation'
            // Le Dropdown envoie une valeur simple (matchMode: EQUALS), pas un objet 'constraints'
            // On vérifie les deux structures possibles pour plus de robustesse.
            $regionFilter = $filters['region.designation']['value'] ?? $filters['region.designation']['constraints'][0]['value'] ?? null;
            if ($regionFilter) {
                // Utiliser 'where' pour une correspondance exacte car c'est un Dropdown
                $query->whereHas('region', fn($q) => $q->where('designation', $regionFilter));
            }
        }

        // Gestion du tri
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        if (str_contains($sortField, '.')) {
            // Tri sur une relation (ex: region.designation)
            [$relation, $field] = explode('.', $sortField);
            if ($relation === 'region') {
                $query->join('regions', 'zones.region_id', '=', 'regions.id')
                      ->orderBy("regions.designation", $sortOrder)
                      ->select('zones.*'); // Important pour éviter les conflits d'ID
            }
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = $request->input('rows', 10);

        return Inertia::render('Configurations/Zones', [
            'zones' => $query->paginate($perPage)->withQueryString(),
            'regions' => Region::all(['id', 'designation', 'code']),
            'filters' => $request->input('filters'),
            'queryParams' => $request->all(['page', 'rows', 'sortField', 'sortOrder', 'filters']),
        ]);
    }

    /**
     * Applique un filtre simple sur une colonne.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param string $field
     */
    private function applyColumnFilter(&$query, $filters, $field)
    {
        // Les filtres de colonne de type texte envoient un objet 'constraints'
        $filterValue = $filters[$field]['constraints'][0]['value'] ?? null;
        if ($filterValue) {
            // Utiliser 'like' avec 'starts with' comme défini dans le frontend
            $query->where($field, 'like', $filterValue . '%');
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // 'name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'required|exists:regions,id',
            'nomenclature' => 'required|string|max:255|unique:zones,nomenclature',
            'number' => 'nullable|integer',
        ]);

        Zone::create($validated);

        return redirect()->route('zones.index')->with('success', 'Zone créée avec succès.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            // 'name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'required|exists:regions,id',
            'nomenclature' => 'required|string|max:255|unique:zones,nomenclature,' . $zone->id,
            'number' => 'nullable|integer',
        ]);

        $zone->update($validated);

        return redirect()->route('zones.index')->with('success', 'Zone mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Zone $zone)
    {
        try {
            $zone->delete();
            return redirect()->route('zones.index')->with('success', 'Zone supprimée avec succès.');
        } catch (\Exception $e) {
            // Gérer le cas où la zone est liée à d'autres enregistrements
            return redirect()->back()->with('error', 'Impossible de supprimer cette zone car elle est utilisée ailleurs.');
        }
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:zones,id',
        ]);

        DB::beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($validated['ids'] as $id) {
                $zone = Zone::find($id);
                if ($zone) {
                    // Vérifier si la zone peut être supprimée (pas de dépendances)
                    // Exemple : if ($zone->activities()->count() > 0) continue;
                    $zone->delete();
                    $deletedCount++;
                }
            }

            if ($deletedCount < count($validated['ids'])) {
                DB::rollBack();
                $notDeletedCount = count($validated['ids']) - $deletedCount;
                return redirect()->back()->with('error', "{$notDeletedCount} zone(s) n'ont pas pu être supprimées car elles sont utilisées.");
            }

            DB::commit();
            return redirect()->route('zones.index')->with('success', 'Les zones sélectionnées ont été supprimées.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression en masse.');
        }
    }
}
