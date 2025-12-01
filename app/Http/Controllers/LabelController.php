<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelCharacteristic;
use Illuminate\Http\Request;

use Inertia\Inertia;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Label::with('labelCharacteristics');

        if ($request->has('search')) {
            $query->where('designation', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Configurations/Labels', [
            'labels' => $query->paginate(10),
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
            'characteristics' => 'nullable|array',
            'characteristics.*.name' => 'required|string|max:255',
            'characteristics.*.type' => 'required|string|in:text,number,date,boolean,select,file',
            'characteristics.*.is_required' => 'required|boolean',
        ]);
        $label = Label::create($validated);

        if (isset($validated['characteristics'])) {
            foreach ($validated['characteristics'] as $char) {
                $label->labelCharacteristics()->create($char);
            }
        }

        return redirect()->route('labels.index')->with('success', 'Label créé avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Label $label)
    {
        return Inertia::render('Labels', [
            'label' => $label->load('labelCharacteristics'),
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
            'characteristics' => 'nullable|array',
            'characteristics.*.id' => 'nullable|integer',
            'characteristics.*.name' => 'required|string|max:255',
            'characteristics.*.type' => 'required|string|in:text,number,date,boolean,select,file',
            'characteristics.*.is_required' => 'required|boolean',
        ]);
        $label->update($validated);

        if (isset($validated['characteristics'])) {
            $incomingIds = collect($validated['characteristics'])->pluck('id')->filter();

            // Supprimer les caractéristiques qui ne sont plus présentes
            $label->labelCharacteristics()->whereNotIn('id', $incomingIds)->delete();

            foreach ($validated['characteristics'] as $charData) {
                // Mettre à jour ou créer une nouvelle caractéristique
                $label->labelCharacteristics()->updateOrCreate( // Gérer les mises à jour des caractéristiques
                    ['id' => $charData['id']],
                    [
                        'name' => $charData['name'],
                        'type' => $charData['type'],
                        'is_required' => $charData['is_required'],
                    ]
                );
            }
        }
        return redirect()->route('labels.index')->with('success', 'Label mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Label $label)
    {
        $label->labelCharacteristics()->delete();
        $label->delete();
        return redirect()->route('labels.index')->with('success', 'Label supprimé avec succès.');
    }
}
