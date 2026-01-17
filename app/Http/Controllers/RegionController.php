<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Inertia\Inertia;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
           $query = Region::query();

        // Gestion du tri
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Gestion des filtres
        if ($request->has('filters')) {
            $filters = $request->input('filters');

            // Filtre global
            if (!empty($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('designation', 'like', '%' . $globalFilter . '%')
                      ->orWhere('type_centrale', 'like', '%' . $globalFilter . '%')
                      ->orWhere('code', 'like', '%' . $globalFilter . '%')
                      ->orWhere('status', 'like', '%' . $globalFilter . '%');
                });
            }

            // Filtre par désignation
            if (!empty($filters['designation']['constraints'][0]['value'])) {
                $query->where('designation', 'like', $filters['designation']['constraints'][0]['value'] . '%');
            }

            // Filtre par type de centrale (MultiSelect)
            if (!empty($filters['type_centrale']['value'])) {
                $query->whereIn('type_centrale', $filters['type_centrale']['value']);
            }

            // Filtre par puissance (Range)
            if (isset($filters['puissance_centrale']['value'])) {
                $range = $filters['puissance_centrale']['value'];
                if (is_array($range) && isset($range[0]) && isset($range[1])) {
                    $query->whereBetween('puissance_centrale', [$range[0], $range[1]]);
                }
            }

            // Filtre par statut
            if (!empty($filters['status']['value'])) {
                $query->where('status', $filters['status']['value']);
            }
        }

        $perPage = $request->input('rows', 15);

        return Inertia::render('Configurations/Regions', [
            'regions' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['filters']),
            'queryParams' => $request->all(['sortField', 'sortOrder', 'rows']),
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Regions');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255|unique:regions',
            'type_centrale' => 'nullable|string|max:255',
            'puissance_centrale' => 'nullable|numeric|between:0,999999.99',
            'code' => 'nullable|string|max:255,unique:regions',
        ]);
        $region = Region::create($validated);

        return redirect()->route('regions.index')->with('success', 'Région créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Region $region)
    {
        // Not typically used for Inertia CRUD, but can be implemented if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        return Inertia::render('Regions', [
            'region' => $region,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255|unique:regions,designation,' . $region->id,
            'type_centrale' => 'nullable|string|max:255',
            'puissance_centrale' => 'nullable|numeric|between:0,999999.99',
              'code' => 'nullable|string|max:255,unique:regions',
        ]);
        $region->update($validated);

        return redirect()->route('regions.index')->with('success', 'Région mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        $region->delete();
        return redirect()->route('regions.index')->with('success', 'Région supprimée avec succès.');
    }

    private function applyColumnFilter(&$query, $filters, $field)
    {
        $filterValue = $filters[$field]['constraints'][0]['value'] ?? null;
        if ($filterValue) {
            $query->where($field, 'like', $filterValue . '%');
        }
    }

    /**
     * Supprime plusieurs régions en une seule fois.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:regions,id',
        ]);

        Region::whereIn('id', $request->ids)->delete();

        return redirect()->route('regions.index')->with('success', 'Les régions sélectionnées ont été supprimées.');
    }
}
