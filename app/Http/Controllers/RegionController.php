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
        $perPage = $request->input('rows', 10); // 'rows' est utilisé par PrimeVue
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filters = $request->input('filters', []);
        $search = $filters['global']['value'] ?? null;

        $query = Region::query();

        // Filtre de date global
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Filtre de recherche global
        if (!empty($filters)) {
            if (isset($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('designation', 'like', "%{$globalFilter}%")
                      ->orWhere('type_centrale', 'like', "%{$globalFilter}%")
                      ->orWhere('code', 'like', "%{$globalFilter}%")
                      ->orWhere('status', 'like', "%{$globalFilter}%");
                });
            }

            // Filtres par colonne (exemple)
            $this->applyColumnFilter($query, $filters, 'designation');
            $this->applyColumnFilter($query, $filters, 'code');
            $this->applyColumnFilter($query, $filters, 'type_centrale');
        }


        // Gestion du tri
        if ($request->has('sortField') && $request->input('sortField')) {
            $sortOrder = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
            $query->orderBy($request->input('sortField'), $sortOrder);
        } else {
            $query->latest(); // Tri par défaut
        }

        // Pagination
        $regions = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Configurations/Regions', [
            'regions' => $regions,
            'filters' => $request->input('filters'),
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
}
