<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InitiativeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Initiative::query();

        // Recherche globale
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $initiatives = $query->orderBy('order')
            ->paginate(10)
            ->appends($request->only('search'));

        return Inertia::render('Admin/Initiatives', [
            'initiatives' => $initiatives,
            'filters'     => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'icon'        => 'required|string|max:255',
            'color'       => 'required|string|max:50',
            'summary'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'metrics'     => 'nullable|json',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active'   => 'boolean',
            'order'       => 'nullable|integer|min:0',
        ]);

        // Gestion de l'image (disk 'media')
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('initiatives', 'media');
        }

        // Décodage des métriques
        if (!empty($validated['metrics'])) {
            $validated['metrics'] = json_decode($validated['metrics'], true);
        } else {
            $validated['metrics'] = [];
        }

        // Ordre par défaut
        if (!isset($validated['order'])) {
            $validated['order'] = Initiative::max('order') + 1;
        }

        Initiative::create($validated);

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative créée avec succès.');
    }

    /**
     * Update an existing initiative.
     */
    public function update(Request $request, Initiative $initiative)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'icon'        => 'required|string|max:255',
            'color'       => 'required|string|max:50',
            'summary'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'metrics'     => 'nullable|json',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active'   => 'boolean',
            'order'       => 'nullable|integer|min:0',
            'delete_image'=> 'boolean',
        ]);

        // Suppression demandée de l'image existante
        if ($request->boolean('delete_image') && $initiative->image) {
            Storage::disk('media')->delete($initiative->image);
            $initiative->image = null;
            $initiative->save(); // on persiste la suppression avant l'update
        }

        // Upload d'une nouvelle image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe (et n'a pas déjà été supprimée)
            if ($initiative->image && !$request->boolean('delete_image')) {
                Storage::disk('media')->delete($initiative->image);
            }
            $validated['image'] = $request->file('image')->store('initiatives', 'media');
        } else {
            // Ne pas écraser l'image existante avec null
            unset($validated['image']);
        }

        // Décodage des métriques
        if (isset($validated['metrics'])) {
            $validated['metrics'] = json_decode($validated['metrics'], true);
        }

        // Retirer le champ virtuel delete_image avant update
        unset($validated['delete_image']);

        $initiative->update($validated);

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative mise à jour avec succès.');
    }

    /**
     * Delete an initiative.
     */
    public function destroy(Initiative $initiative)
    {
        // Supprimer l'image associée sur le disque 'media'
        if ($initiative->image) {
            Storage::disk('media')->delete($initiative->image);
        }

        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative supprimée avec succès.');
    }

    /**
     * Reorder initiatives (drag & drop).
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:initiatives,id',
        ]);

        foreach ($request->order as $index => $id) {
            Initiative::where('id', $id)->update(['order' => $index]);
        }

        return redirect()->route('initiatives.index')
            ->with('success', 'Ordre mis à jour avec succès.');
    }
}
