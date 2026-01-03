<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Zone::query()->with('region');

        if (request()->has('search')) {
            $query->where('title', 'like', '%' . request('search') . '%')
 ->orWhere('description', 'like', '%' . request('search') . '%')
 ->orWhere('nomenclature', 'like', '%' . request('search') . '%')
 ->orWhere('number', 'like', '%' . request('search') . '%');
        }

        return Inertia::render('Configurations/Zones', [
            'zones' => $query->paginate(10),
            'regions' => Region::all(),
            'filters' => request()->only(['search']),
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method is typically used to show a form for creating a new resource.
        // With Inertia.js, the form is often part of the index page or a dedicated create page.
        // If you have a separate page for creating, you would render it here.
        // For now, we'll assume the form is handled on the index page or a modal.
        return Inertia::render('Configurations/Zones', [
            // Any data needed for the create form, e.g., dropdown options
            'zones' => Zone::all(), // Or any other data needed
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:zones,title',
            'description' => 'nullable|string|max:1000',
            'nomenclature' => 'nullable|string|max:255',
            'number' => 'nullable|max:255',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        Zone::create($validated);
        return redirect()->route('zones.index')->with('success', 'Zone créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Zone $zone)
    {
        return Inertia::render('Configurations/Zones', [
            'zone' => $zone,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Zone $zone)
    {
        return Inertia::render('Configurations/Zones', [
            'zone' => $zone,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:zones,title,' . $zone->id,
            'description' => 'nullable|string|max:1000',
            'nomenclature' => 'nullable|string|max:255',
            'number' => 'nullable|max:255',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        $zone->update($validated);

        return redirect()->route('zones.index')->with('success', 'Zone mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Zone $zone)
    {
        $zone->delete();
        return redirect()->route('zones.index')->with('success', 'Zone supprimée avec succès.');
    }
}
