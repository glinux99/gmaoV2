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
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Utilisation de select explicite pour éviter les conflits d'IDs avec les jointures
        $query = StockMovement::query()
            ->with(['movable', 'sourceRegion', 'destinationRegion', 'user'])
            ->select('stock_movements.*');

        // --- NOUVEAU : Gestion du filtre par région pour les articles déplaçables ---
        $regionId = null;
        if ($request->has('filters')) {
            $filters = $request->input('filters');
            // On vérifie si un filtre de région est appliqué (ex: depuis un Dropdown)
            if (isset($filters['region_id']['value'])) {
                $regionId = $filters['region_id']['value'];
            }
        }


        // --- Gestion du Tri ---
        if ($sortField = $request->input('sortField')) {
            $sortOrder = $request->input('sortOrder') === '1' ? 'asc' : 'desc';

            if (str_contains($sortField, '.')) {
                $parts = explode('.', $sortField);
                if ($parts[0] === 'user') {
                    $query->join('users', 'stock_movements.user_id', '=', 'users.id')
                          ->orderBy('users.name', $sortOrder);
                }
                // Ajouter d'autres relations ici si nécessaire
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->latest();
        }

        // --- Gestion du Filtre Global ---
        if ($globalFilter = $request->input('filters.global.value')) {
            $query->where(function ($q) use ($globalFilter) {
                $q->where('type', 'like', "%{$globalFilter}%")
                  ->orWhere('notes', 'like', "%{$globalFilter}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$globalFilter}%"));
            });
        }

        return Inertia::render('Actifs/Movements', [
            'stockMovements' => $query->paginate($request->input('rows', 10))->withQueryString(),
            'regions' => Region::select('id', 'designation')->get(),
            'users' => User::select('id', 'name')->get(),
            // Les articles sont maintenant chargés en fonction du filtre de région
            'movableItems' => [
                'spare_parts' => SparePart::whereHasStockInRegion($regionId)->withStockInRegion($regionId)->get(),
                'equipments' => Equipment::whereHasStockInRegion($regionId)->withStockInRegion($regionId)->get(),
                'meters'    => Meter::where('region_id', $regionId)->orWhereNull('region_id')->get(),
                'keypads'   => Keypad::where('region_id', $regionId)->orWhereNull('region_id')->get(),
                'engins'    => Engin::where('region_id', $regionId)->orWhereNull('region_id')->get(),
            ],
            'queryParams' => $request->all(['sortField', 'sortOrder', 'filters']),
        ]);
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
            'source_region_id' => 'required_if:type,transfer|nullable|exists:regions,id',
            'destination_region_id' => 'required_if:type,transfer|nullable|exists:regions,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'intended_for_user_id' => 'nullable|exists:users,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated['items'] as $item) {
                    $movable = $item['movable_type']::lockForUpdate()->findOrFail($item['movable_id']);
                    $qty = $item['quantity'];

                    // 1. Validation de la logique de stock (Sortie/Transfert)
                    if (in_array($validated['type'], ['exit', 'transfer'])) {
                        if (isset($movable->quantity) && $movable->quantity < $qty) {
                            throw ValidationException::withMessages([
                                'items' => "Stock insuffisant pour {$movable->designation} (Disponible: {$movable->quantity})"
                            ]);
                        }
                    }

                    // 2. Création du mouvement
                    StockMovement::create([
                        'movable_type' => $item['movable_type'],
                        'movable_id' => $item['movable_id'],
                        'quantity' => $qty,
                        'type' => $validated['type'],
                        'source_region_id' => $validated['source_region_id'],
                        'destination_region_id' => $validated['destination_region_id'],
                        'date' => $validated['date'],
                        'notes' => $validated['notes'],
                        'user_id' => Auth::id(),
                        'responsible_user_id' => $validated['responsible_user_id'],
                        'intended_for_user_id' => $validated['intended_for_user_id'],
                    ]);

                    // 3. Mise à jour de l'inventaire selon le type de modèle
                    $this->updateInventory($movable, $item, $validated);
                }
            });

            return redirect()->back()->with('success', 'Mouvements enregistrés avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
/**
     * Update the specified resource in storage.
     */
public function update(Request $request, $id)
{
    // 1. Validation rigoureuse
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

    try {
        DB::transaction(function () use ($validated, $id) {
            // 2. Récupérer les anciens mouvements liés à cette transaction/ID
            // Note: Si vos mouvements sont groupés par un identifiant de transaction (ex: reference_group)
            // Adaptez la requête ci-dessous. Ici, on suppose qu'on remplace le mouvement spécifique.
            $oldMovements = StockMovement::where('id', $id)->lockForUpdate()->get();

            // 3. Rétablir le stock (Annulation de l'ancien état)
            foreach ($oldMovements as $old) {
                $oldMovable = $old->movable()->lockForUpdate()->first();
                if ($oldMovable && !in_array($old->movable_type, [Meter::class, Keypad::class, Engin::class])) {
                    if ($old->type === 'entry') {
                        $oldMovable->decrement('quantity', $old->quantity);
                    } elseif ($old->type === 'exit') {
                        $oldMovable->increment('quantity', $old->quantity);
                    }
                }
            }

            // 4. Supprimer les anciens enregistrements pour repartir à neuf
            StockMovement::where('id', $id)->delete();

            // 5. Créer les nouveaux mouvements et valider le nouveau stock
            foreach ($validated['items'] as $item) {
                $movable = $item['movable_type']::lockForUpdate()->findOrFail($item['movable_id']);

                // Vérification de sécurité pour les sorties
                if (in_array($validated['type'], ['exit', 'transfer'])) {
                    if (isset($movable->quantity) && $movable->quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour {$movable->designation} après modification.");
                    }
                }

                // Création du nouveau mouvement
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

                // Mise à jour finale du stock
                $this->updateInventory($movable, $item, $validated);
            }
        });

        return redirect()->back()->with('success', 'Mise à jour réussie et stock synchronisé.');

    } catch (\Exception $e) {
        return redirect()->back()->withErrors(['error' => "Échec de la mise à jour : " . $e->getMessage()]);
    }
}
    /**
     * Logique de mise à jour robuste de l'inventaire
     */
    private function updateInventory($movable, $item, $validated)
    {
        // On ne procède à cette logique que pour les SparePart qui ont une quantité.
        if ($item['movable_type'] === SparePart::class) {
            $quantity = $item['quantity'];

            if ($validated['type'] === 'entry' && isset($validated['destination_region_id'])) {
                // Incrémente le stock de la pièce dans la région de destination.
                // Crée la pièce dans cette région si elle n'existe pas.
                $part = SparePart::firstOrCreate(
                    ['reference' => $movable->reference, 'region_id' => $validated['destination_region_id']], // Attributs de recherche
                    [ // Attributs de création
                        'label_id' => $movable->label_id,
                        'price' => $movable->price,
                        'min_quantity' => $movable->min_quantity,
                        'quantity' => 0,
                        'user_id' => Auth::id() // Ajout du user_id ici
                    ]
                );
                $part->increment('quantity', $quantity);

            } elseif ($validated['type'] === 'exit' && isset($validated['source_region_id'])) {
                // Décrémente le stock de la pièce dans la région source.
                $part = SparePart::where('reference', $movable->reference)
                                 ->where('region_id', $validated['source_region_id'])->first();
                if ($part) {
                    $part->decrement('quantity', $quantity);
                }

            } elseif ($validated['type'] === 'transfer') {
                // Décrémente la source
                SparePart::where('reference', $movable->reference)
                         ->where('region_id', $validated['source_region_id'])
                         ->decrement('quantity', $quantity);
                // Incrémente la destination (en la créant si besoin)
                $destPart = SparePart::firstOrCreate(
                    ['reference' => $movable->reference, 'region_id' => $validated['destination_region_id']],
                    [ // Attributs de création
                        'label_id' => $movable->label_id,
                        'price' => $movable->price,
                        'min_quantity' => $movable->min_quantity,
                        'quantity' => 0,
                        'user_id' => Auth::id() // Ajout du user_id ici
                    ]
                );
                $destPart->increment('quantity', $quantity);
            }
        } else {
             // Logique pour les autres types de modèles (Equipments, Meters, etc.)
             if ($validated['type'] === 'entry') {
                $movable->increment('quantity', $item['quantity']);
            } elseif ($validated['type'] === 'exit') {
                $movable->decrement('quantity', $item['quantity']);
            }
        }
    }

    /**
 * Display the specified resource.
 */
 public function show(StockMovement $stockMovement)
 {
 //
 }

 /**
     * Suppression avec Rollback de stock (Annulation)
     */
    public function destroy(StockMovement $stockMovement)
    {
        DB::transaction(function () use ($stockMovement) {
            $movable = $stockMovement->movable()->lockForUpdate()->first();

            if ($movable) {
                // On inverse la logique initiale
                if ($stockMovement->type === 'entry') {
                    $movable->decrement('quantity', $stockMovement->quantity);
                } elseif ($stockMovement->type === 'exit') {
                    $movable->increment('quantity', $stockMovement->quantity);
                } elseif ($stockMovement->type === 'transfer' && isset($movable->region_id)) {
                    $movable->update(['region_id' => $stockMovement->source_region_id]);
                }
            }

            $stockMovement->delete();
        });

        return redirect()->back()->with('success', 'Mouvement annulé et stock rétabli.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach($request->ids as $id) {
            $movement = StockMovement::find($id);
            if($movement) $this->destroy($movement);
        }

        return redirect()->back()->with('success', 'Sélection annulée avec succès.');
    }
}
