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
    public function __construct()
    {
        $this->middleware('can:read-stock-movement')->only(['index', 'show']);
        $this->middleware('can:create-stock-movement')->only(['store']);
        $this->middleware('can:update-stock-movement')->only(['update']);
        $this->middleware('can:delete-stock-movement')->only(['destroy']);
        $this->middleware('can:bulk-delete-stock-movement')->only(['bulkDestroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Utilisation de select explicite pour éviter les conflits d'IDs avec les jointures
        $query = StockMovement::with([
                'movable' => function ($morphTo) {
                    $morphTo->morphWith([
                        SparePart::class => ['label'], // Charger la relation 'label' uniquement pour les SparePart
                    ]);
                },
                'sourceRegion',
                'destinationRegion',
                'user'])
            ->select('stock_movements.*');

        // --- Gestion des filtres de la requête ---
        $filters = $request->input('filters', []);
        $regionId = $filters['region_id']['value'] ?? null;
        $movementType = $filters['type']['value'] ?? $request->input('movementType'); // ajouter ici ou recuprer le mouvement type
        $globalFilter = $filters['global']['value'] ?? null;

        // Si le mouvement est de type 'exit' ou 'entry' et que la région source est égale à la région de destination,
        // cela signifie qu'il n'y a pas de mouvement inter-régional, donc on peut ignorer ces filtres.
        if (in_array($movementType, ['exit', 'entry']) && isset($filters['source_region_id']['value']) && isset($filters['destination_region_id']['value']) && $filters['source_region_id']['value'] === $filters['destination_region_id']['value']) {
            unset($filters['source_region_id']);
            unset($filters['destination_region_id']);
        }

        // Si la région source est vide, utiliser la région de destination pour le filtre
        if (empty($filters['source_region_id']['value']) && !empty($filters['destination_region_id']['value'])) {
            $filters['source_region_id']['value'] = $filters['destination_region_id']['value'];
        }

        // Si la région de destination est vide, utiliser la région source pour le filtre
        if (empty($filters['destination_region_id']['value']) && !empty($filters['source_region_id']['value'])) {
            $filters['destination_region_id']['value'] = $filters['source_region_id']['value'];
        }
        $sortField = $request->input('sortField');


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
        if ($globalFilter) {
            $query->where(function ($q) use ($globalFilter) {
                $q->where('type', 'like', "%{$globalFilter}%")
                  ->orWhere('notes', 'like', "%{$globalFilter}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$globalFilter}%"));
            });
        }

        // --- NOUVEAU : Liste "maîtresse" de tous les articles pour les entrées ---
        // Charge tous les articles uniques, sans filtre de région, pour les mouvements d'entrée.
        // Cela évite de recharger la page et optimise la sélection.
        $masterMovableItems = [
            'spare_parts' => SparePart::with('label:id,designation')->select('id', 'reference', 'label_id')->distinct('reference')->get(),
            'equipments'  => Equipment::select('id', 'tag', 'designation')->get(),
            'meters'      => Meter::select('id', 'serial_number', 'model')->get(),
            'keypads'     => Keypad::select('id', 'serial_number', 'model')->get(),
            'engins'      => Engin::select('id', 'designation', 'immatriculation')->get(),
        ];

        // --- Chargement conditionnel des articles déplaçables ---
        $movableItems = [
            'spare_parts' => collect(),
            'equipments'  => collect(),
            'meters'      => collect(),
            'keypads'     => collect(),
            'engins'      => collect(),
        ];


        // dd(SparePart::whereHasStockInRegion(1)->withStockInRegion(1)->get());
        // On charge les articles d'une région seulement si c'est une sortie ou un transfert.
        if ($regionId && in_array($movementType, ['exit', 'transfer'])) {
            $movableItems['spare_parts'] = SparePart::with('label:id,designation')->whereHasStockInRegion($regionId)->withStockInRegion($regionId)->get();
            $movableItems['equipments'] = Equipment::whereHasStockInRegion($regionId)->withStockInRegion($regionId)->get();
            $movableItems['meters'] = Meter::where('region_id', $regionId)->orWhereNull('region_id')->get();
            $movableItems['keypads'] = Keypad::where('region_id', $regionId)->orWhereNull('region_id')->get();
            $movableItems['engins'] = Engin::where('region_id', $regionId)->orWhereNull('region_id')->get();
        }
        // dd($movableItems, $regionId, $movementType);
        return Inertia::render('Actifs/Movements', [
            'stockMovements' => $query->paginate($request->input('rows', 10))->withQueryString(),
            'regions' => Region::select('id', 'designation')->get(),
            'users' => User::select('id', 'name')->get(),
            'movableItems' => $movableItems,
            'masterMovableItems' => $masterMovableItems, // On passe la nouvelle liste à Vue
            // On ne passe que les queryParams utiles pour éviter de surcharger la requête
            'queryParams' => $request->only(['sortField', 'sortOrder', 'filters']),
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
                    $stock_at_movement = 0; // Valeur par défaut

                    // 1. Validation de la logique de stock (Sortie/Transfert)
                    if (in_array($validated['type'], ['exit', 'transfer'])) {
                        $regionId = $validated['source_region_id'];
                        // Pour les SparePart, le stock est dans la table elle-même par région
                        if ($item['movable_type'] === SparePart::class) {
                            $partInRegion = SparePart::where('reference', $movable->reference)->where('region_id', $regionId)->first();
                            $stock_at_movement = $partInRegion ? $partInRegion->quantity : 0;
                        } else { // Pour les autres, on suppose une relation ou un champ direct
                            $stock_at_movement = $movable->stock_in_region ?? $movable->quantity ?? 0;
                        }

                        if ($stock_at_movement < $qty) {
                            throw ValidationException::withMessages([
                                'items' => "Stock insuffisant pour {$movable->designation} (Disponible: {$stock_at_movement})"
                            ]);
                        }
                    } elseif ($validated['type'] === 'entry') {
                        $regionId = $validated['destination_region_id'];
                        // On récupère le stock *avant* l'incrémentation
                        $partInRegion = SparePart::where('reference', $movable->reference)->where('region_id', $regionId)->first();
                        $stock_at_movement = $partInRegion ? $partInRegion->quantity : 0;
                    }

                    // 2. Création du mouvement
                    StockMovement::create([
                        'stock_at_movement' => $stock_at_movement, // Sauvegarde du stock au moment du mouvement
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
                $part = SparePart::updateOrCreate(
                    ['reference' => $movable->reference, 'region_id' => $validated['destination_region_id']], // Attributs de recherche
                    [ // Attributs de création
                        'label_id' => $movable->label_id,
                        'price' => $movable->price,
                        'min_quantity' => $movable->min_quantity,
                        'quantity' => DB::raw("quantity + $quantity"),
                        'user_id' => Auth::id() // Ajout du user_id ici
                    ]
                );

                // Met à jour la quantité de la pièce parente (sans région)
                SparePart::where('reference', $movable->reference)
                    ->whereNull('region_id')->increment('quantity', $quantity);

            } elseif ($validated['type'] === 'exit' && isset($validated['source_region_id'])) {
                // Décrémente le stock de la pièce dans la région source.
                $part = SparePart::where('reference', $movable->reference)
                                 ->where('region_id', $validated['source_region_id'])->first();
                if ($part) {
                    $part->decrement('quantity', $quantity);
                }

                // Met à jour la quantité de la pièce parente (sans région)
                SparePart::where('reference', $movable->reference)
                    ->whereNull('region_id')->decrement('quantity', $quantity);

            } elseif ($validated['type'] === 'transfer') {
                // Décrémente la source
                SparePart::where('reference', $movable->reference)
                         ->where('region_id', $validated['source_region_id'])
                         ->decrement('quantity', $quantity);
                // Incrémente la destination (en la créant si besoin)
                $destPart = SparePart::updateOrCreate(
                    ['reference' => $movable->reference, 'region_id' => $validated['destination_region_id']],
                    [ // Attributs de création
                        'label_id' => $movable->label_id,
                        'price' => $movable->price,
                        'min_quantity' => $movable->min_quantity,
                        'quantity' => DB::raw("quantity + $quantity"),
                        'user_id' => Auth::id() // Ajout du user_id ici
                    ]
                );
            }
        } elseif ($item['movable_type'] === Equipment::class) {
            $quantity = $item['quantity'];

            if ($validated['type'] === 'entry' && isset($validated['destination_region_id'])) {
                $equipment = Equipment::firstOrCreate(
                    [
                        'tag' => $movable->tag,
                        'region_id' => $validated['destination_region_id']
                    ],
                    [
                        'designation' => $movable->designation,
                        'brand' => $movable->brand,
                        'model' => $movable->model,
                        'serial_number' => null,
                        'status' => 'en stock',
                        'equipment_type_id' => $movable->equipment_type_id,
                        'user_id' => Auth::id(),
                        'quantity' => 0,
                    ]
                );
                $equipment->increment('quantity', $quantity);
            } elseif ($validated['type'] === 'exit' && isset($validated['source_region_id'])) {
                $equipment = Equipment::where('tag', $movable->tag)
                                 ->where('region_id', $validated['source_region_id'])->first();
                if ($equipment) {
                    $equipment->decrement('quantity', $quantity);
                }
            } elseif ($validated['type'] === 'transfer') {
                // Décrémente la source
                Equipment::where('tag', $movable->tag)
                         ->where('region_id', $validated['source_region_id'])
                         ->decrement('quantity', $quantity);
                // Incrémente la destination (en la créant si besoin)
                $this->updateInventory($movable, ['movable_type' => Equipment::class, 'quantity' => $quantity], ['type' => 'entry', 'destination_region_id' => $validated['destination_region_id']]);
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
