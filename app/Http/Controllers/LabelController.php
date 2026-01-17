<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelCharacteristic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LabelController extends Controller
{
    public function __construct()
    {
        // Vous pouvez décommenter et ajuster les permissions selon vos besoins
        // $this->middleware('can:read-label')->only(['index']);
        // $this->middleware('can:create-label')->only(['store']);
        // $this->middleware('can:update-label')->only(['update']);
        // $this->middleware('can:delete-label')->only(['destroy']);
        // $this->middleware('can:bulk-delete-label')->only(['bulkDestroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1',
            'sortField' => 'nullable|string',
            'sortOrder' => 'nullable|in:asc,desc',
            'search' => 'nullable|string',
        ]);

        $query = Label::with('labelCharacteristics')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('designation', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('sortField')) {
            $query->orderBy($request->input('sortField'), $request->input('sortOrder', 'asc'));
        }

        return Inertia::render('Configurations/Labels', [
            'labels' => $query->paginate($request->input('per_page', 15))->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255|unique:labels,designation',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'characteristics' => 'nullable|array',
            'characteristics.*.name' => 'required|string|max:255',
            'characteristics.*.type' => 'required|string|in:text,number,date,file,boolean,select',
            'characteristics.*.is_required' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $label = Label::create([
                'designation' => $validated['designation'],
                'description' => $validated['description'],
                'color' => $validated['color'],
            ]);

            if (!empty($validated['characteristics'])) {
                foreach ($validated['characteristics'] as $charData) {
                    $label->labelCharacteristics()->create($charData);
                }
            }
        });

        return redirect()->route('labels.index')->with('success', 'Label créé avec succès.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Label $label)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255|unique:labels,designation,' . $label->id,
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'characteristics' => 'nullable|array',
            'characteristics.*.id' => 'nullable|integer',
            'characteristics.*.name' => 'required|string|max:255',
            'characteristics.*.type' => 'required|string|in:text,number,date,file,boolean,select',
            'characteristics.*.is_required' => 'boolean',
        ]);

        DB::transaction(function () use ($label, $validated) {
            $label->update([
                'designation' => $validated['designation'],
                'description' => $validated['description'],
                'color' => $validated['color'],
            ]);

            $incomingCharIds = collect($validated['characteristics'])->pluck('id')->filter()->all();
            $label->labelCharacteristics()->whereNotIn('id', $incomingCharIds)->delete();

            if (!empty($validated['characteristics'])) {
                foreach ($validated['characteristics'] as $charData) {
                    $label->labelCharacteristics()->updateOrCreate(
                        ['id' => $charData['id'] ?? null, 'label_id' => $label->id],
                        $charData
                    );
                }
            }
        });

        return redirect()->route('labels.index')->with('success', 'Label mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Label $label)
    {
        // Ajouter une logique pour vérifier si le label est utilisé avant de supprimer
        $label->delete();
        return redirect()->route('labels.index')->with('success', 'Label supprimé avec succès.');
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:labels,id',
        ]);

        DB::transaction(function () use ($validated) {
            // Supprimer d'abord les caractéristiques associées pour maintenir l'intégrité
            LabelCharacteristic::whereIn('label_id', $validated['ids'])->delete();
            // Ensuite, supprimer les labels
            Label::whereIn('id', $validated['ids'])->delete();
        });

        return redirect()->route('labels.index')->with('success', 'Labels sélectionnés supprimés avec succès.');
    }
}
