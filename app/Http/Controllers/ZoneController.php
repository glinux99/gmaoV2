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
    // 1. On commence par définir explicitement ce qu'on veut extraire de la table zones
    $query = Zone::select('zones.*')->with('region');

    // --- GESTION DES FILTRES ---
    $filters = $request->input('filters', []);

    // if (!empty($filters)) {
    //     // Filtre global
    //     if (!empty($filters['global']['value'])) {
    //         $globalFilter = $filters['global']['value'];
    //         $query->where(function ($q) use ($globalFilter) {
    //             // Utilisation du préfixe 'zones.' pour éviter toute ambiguïté
    //             $q->where('zones.title', 'like', "%{$globalFilter}%")
    //               ->orWhere('zones.nomenclature', 'like', "%{$globalFilter}%")
    //               ->orWhereHas('region', function($subQ) use ($globalFilter) {
    //                   $subQ->where('designation', 'like', "%{$globalFilter}%");
    //               });
    //         });
    //     }

    //     // Filtres par colonne
    //     $this->applyColumnFilter($query, $filters, 'nomenclature');
    //     $this->applyColumnFilter($query, $filters, 'title');

    //     // Filtre spécifique pour la relation Region
    //     if (!empty($filters['region.designation']['constraints'][0]['value'])) {
    //         $regionVal = $filters['region.designation']['constraints'][0]['value'];
    //         $query->whereHas('region', fn($q) => $q->where('designation', 'like', $regionVal . '%'));
    //     }
    // }

    // // --- GESTION DU TRI ---
    // $sortField = $request->input('sortField', 'created_at');
    // $sortOrder = $request->input('sortOrder', 'desc');

    // if ($sortField === 'region.designation') {
    //     $query->join('regions', 'zones.region_id', '=', 'regions.id')
    //           ->orderBy('regions.designation', $sortOrder)
    //           // On ré-insiste sur zones.* pour que 'number' et 'nomenclature'
    //           // ne soient pas pollués par la table jointe
    //           ->select('zones.*');
    // } else {
    //     // Sécurité : si le sortField ne contient pas de point, on préfixe par zones.
    //     $safeSortField = str_contains($sortField, '.') ? $sortField : "zones.{$sortField}";
    //     $query->orderBy($safeSortField, $sortOrder);
    // }

    return Inertia::render('Configurations/Zones', [
        'zones' => $query->paginate($request->input('rows', 10))->withQueryString(),
        'regions' => Region::orderBy('designation')->get(['id', 'designation', 'code']),
        'filters' => $filters,
    ]);
}

/**
 * Correction de la méthode helper pour inclure le préfixe de table
 */
private function applyColumnFilter(&$query, $filters, $field)
{
    $filterValue = $filters[$field]['constraints'][0]['value'] ?? null;
    if ($filterValue) {
        // On force le préfixe "zones." pour éviter les conflits lors des jointures
        $query->where("zones.{$field}", 'like', $filterValue . '%');
    }
}

    /**
     * Applique un filtre simple sur une colonne.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param string $field
     */

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
            return redirect()->route('zones.index')->with('error', 'Impossible de supprimer cette zone car elle est utilisée ailleurs.');
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
                return redirect()->route('zones.index')->with('error', "{$notDeletedCount} zone(s) n'ont pas pu être supprimées car elles sont utilisées.");
            }

            DB::commit();
            return redirect()->route('zones.index')->with('success', 'Les zones sélectionnées ont été supprimées.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('zones.index')->with('error', 'Une erreur est survenue lors de la suppression en masse.');
        }
    }
}
