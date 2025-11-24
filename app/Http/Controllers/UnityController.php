<?php

namespace App\Http\Controllers;

use App\Models\Unity;
use Inertia\Inertia;
use Illuminate\Http\Request;

class UnityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Unity::query();

        if (request()->has('search')) {
            $query->where('designation', 'like', '%' . request('search') . '%')
                  ;
        }

        return Inertia::render('Configurations/Unities', [
            'unities' => $query->get(),
            'filters' => request()->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Unities');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'abreviation' => 'nullable|string',
        ]);
        $unity = Unity::create($validated);

        return redirect()->route('unities.index')->with('success', 'Unité créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unity $unity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unity $unity)
    {
        return Inertia::render('Unities', [
            'unity' => $unity,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unity $unity)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'abreviation' => 'nullable|string',
        ]);
        $unity->update($validated);

        return redirect()->route('unities.index')->with('success', 'Unité mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unity $unity)
    {
        $unity->delete();
        return redirect()->route('unities.index')->with('success', 'Unité supprimée avec succès.');
    }
}
