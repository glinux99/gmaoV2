<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Region;
use App\Models\User;
use App\Models\SparePart;
use App\Models\Equipment;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Engin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockMovements = StockMovement::with(['movable', 'sourceRegion', 'destinationRegion', 'user'])
            ->latest('created_at')
            ->paginate(10);

        return Inertia::render('Actifs/Movements', [
            'stockMovements' => $stockMovements,
            'regions' => Region::all(),
            'users' => User::all(), // Used for responsible_user_id and intended_for_user_id
            'movableItems' => [
                'spare_parts' => SparePart::all(),
                'equipments' => Equipment::all(),
                'meters' => Meter::all(),
                'keypads' => Keypad::all(),
                'engins' => Engin::all(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Le formulaire est généralement géré via un dialogue dans Inertia, donc cette méthode peut ne pas être nécessaire.
        // Si une page dédiée est requise, vous pouvez la retourner ici.
        return redirect()->route('stock-movements.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.movable_type' => 'required|string',
            'items.*.movable_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'type' => 'required|in:entry,exit,transfer',
            'source_region_id' => 'nullable|exists:regions,id',
            'destination_region_id' => 'nullable|exists:regions,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'intended_for_user_id' => 'nullable|exists:users,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                $movable = $item['movable_type']::findOrFail($item['movable_id']);

                StockMovement::create([
                    'movable_type' => $item['movable_type'],
                    'movable_id' => $item['movable_id'],
                    'quantity' => $item['quantity'],
                    'type' => $validated['type'],
                    'source_region_id' => $validated['source_region_id'],
                    'destination_region_id' => $validated['destination_region_id'],
                    'date' => $validated['date'],
                    'notes' => $validated['notes'],
                    'user_id' => Auth::id(),
                    'responsible_user_id' => $validated['responsible_user_id'],
                    'intended_for_user_id' => $validated['intended_for_user_id'],
                ]);

                // --- Logique de mise à jour de l'inventaire ---
                $serializedModels = [Meter::class, Keypad::class, Engin::class];

                if (in_array($item['movable_type'], $serializedModels)) {
                    // Pour les articles sérialisés (Compteurs, Claviers, Engins)
                    if ($validated['type'] === 'transfer') {
                        // En cas de transfert, on met à jour la région de l'article lui-même.
                        $movable->region_id = $validated['destination_region_id'];
                        $movable->save();
                    }
                    // Pour une 'sortie', on pourrait changer le statut de l'article (ex: 'assigned')
                    // Pour une 'entrée', on pourrait le changer en 'in_stock'
                    // Cette logique peut être ajoutée ici au besoin.

                } else {
                    // Pour les articles avec quantité (Pièces détachées, Equipements non sérialisés)
                    if ($validated['type'] === 'entry') {
                        $movable->increment('quantity', $item['quantity']);
                    } elseif ($validated['type'] === 'exit') {
                        $movable->decrement('quantity', $item['quantity']);
                    }
                }
            }
        });

        return redirect()->route('stock-movements.index')->with('success', 'Mouvement de stock créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        // Généralement pas utilisé avec Inertia, les détails sont chargés dans l'index ou via un dialogue.
        return redirect()->route('stock-movements.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockMovement $stockMovement)
    {
        // Le formulaire est géré via un dialogue dans Inertia.
        return redirect()->route('stock-movements.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockMovement $stockMovement)
    {
        // La mise à jour d'un mouvement multiple n'est pas standard.
        // On garde la logique de mise à jour pour un mouvement unique.
        $validated = $request->validate([
            'movable_type' => 'required|string',
            'movable_id' => 'required|integer',
            'type' => 'required|in:entry,exit,transfer',
            'quantity' => 'required|integer|min:1',
            'source_region_id' => 'nullable|exists:regions,id',
            'destination_region_id' => 'nullable|exists:regions,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'intended_for_user_id' => 'nullable|exists:users,id',
        ]);

        // La modification d'un mouvement de stock peut être complexe
        // car elle implique d'annuler l'ancien mouvement sur le stock et d'appliquer le nouveau.
        // Pour l'instant, nous mettons simplement à jour les informations du mouvement sans ajuster le stock.
        // Une logique plus robuste serait nécessaire pour un cas d'utilisation réel.

        $stockMovement->update($validated);

        return redirect()->route('stock-movements.index')->with('success', 'Mouvement de stock mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockMovement $stockMovement)
    {
        // Comme pour la mise à jour, la suppression devrait idéalement "annuler" l'effet du mouvement sur le stock.
        // Par exemple, si c'était une 'sortie', la suppression devrait ré-incrémenter le stock.
        // Pour simplifier, nous supprimons juste l'enregistrement.

        $stockMovement->delete();

        return redirect()->route('stock-movements.index')->with('success', 'Mouvement de stock supprimé avec succès.');
    }
      public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:stock_movements,id',
        ]);

        StockMovement::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('stock-movements.index')->with('success', 'Mouvements de stock sélectionnés supprimés avec succès.');
    }
}

    /**
     * Remove the specified resources from storage.
     */

