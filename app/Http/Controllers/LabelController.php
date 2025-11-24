<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\Request;

use Inertia\Inertia;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Label::query();

        if ($request->has('search')) {
            $query->where('designation', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Configurations/Labels', [
            'labels' => $query->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Labels');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
        ]);
        $label = Label::create($validated);

        return redirect()->route('labels.index')->with('success', 'Label créé avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Label $label)
    {
        return Inertia::render('Labels', [
            'label' => $label,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Label $label)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
        ]);
        $label->update($validated);

        return redirect()->route('labels.index')->with('success', 'Label mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Label $label)
    {
        $label->delete();
        return redirect()->route('labels.index')->with('success', 'Label supprimé avec succès.');
    }
}
