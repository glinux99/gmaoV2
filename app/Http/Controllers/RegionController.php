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
    public function index()
    {
        $query = Region::query();

        if (request()->has('search')) {
            $query->where('designation', 'like', '%' . request('search') . '%')
                ->orWhere('type_centrale', 'like', '%' . request('search') . '%');
        }

        return Inertia::render('Configurations/Regions', [
            'regions' => $query->get(),
            'filters' => request()->only(['search']),
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
}
