<?php

namespace App\Http\Controllers;

use App\Models\Engin;
use App\Models\Region;
use Inertia\Inertia;
use Illuminate\Http\Request;

class EnginController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Engin::query();

        if (request()->has('search')) {
            $query->where('designation', 'like', '%' . request('search') . '%')
                ->orWhere('type', 'like', '%' . request('search') . '%')
                ->orWhere('immatriculation', 'like', '%' . request('search') . '%');
        }

        return Inertia::render('Configurations/Engins', [
            'engins' => $query->get(),
            'regions'=> Region::query()->get(),
            'filters' => request()->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Engins');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'immatriculation' => 'nullable|string|max:255',
            'date_mise_en_service' => 'nullable|date',
            'etat' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $engin = Engin::create($validated);

        return redirect()->route('engins.index')->with('success', 'Engin créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Engin $engin)
    {
        // Not typically used for Inertia CRUD, but can be implemented if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Engin $engin)
    {
        return Inertia::render('Engins', [
            'engin' => $engin,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Engin $engin)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'immatriculation' => 'nullable|string|max:255',
            'date_mise_en_service' => 'nullable|date',
            'etat' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $engin->update($validated);

        return redirect()->route('engins.index')->with('success', 'Engin mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Engin $engin)
    {
        $engin->delete();
        return redirect()->route('engins.index')->with('success', 'Engin supprimé avec succès.');
    }
}
