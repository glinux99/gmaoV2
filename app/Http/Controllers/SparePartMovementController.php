<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SparePart;
use App\Models\User;
use App\Models\SparePartMovement;
use App\Models\Unity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SparePartMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = SparePartMovement::with(['sparePart.region', 'user']);

        if (request()->has('search')) {
            $search = request('search');
            $query->where('type', 'like', '%' . $search . '%')
                ->orWhere('location', 'like', '%' . $search . '%')
                ->orWhereHas('sparePart', function ($q) use ($search) {
                    $q->where('reference', 'like', '%' . $search . '%');
                })
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('sparePart.region', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }
        return Inertia::render('Actifs/SparePartMovements', [
            'sparePartMovements' => $query->paginate(1000),
            'filters' => request()->only(['search']), // Changed from spareParts to sparePartMovements
            'spareParts' => SparePart::with('characteristics')->get(), // To select a spare part for the movement,
            'regions' => Region::get(), // To select a region for the spare part,
            'unities' => Unity::get(), // To select a unity for the spare part,
            'users' => User::all(['id', 'name']), // To select a user for the movement
            'sparePartCharacteristics' => [], // Existing characteristics for the spare part
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Actifs/SparePartMovements', [
            'spareParts' => SparePart::all(['id', 'reference']),
               'regions' => Region::get(), // To select a region for the spare part,
            'unities' => Unity::get(), // To select a unity for the spare part,
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('createSparePartMovement', [
            'spare_part_id' => 'required|exists:spare_parts,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:entree,sortie',
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'notes' => 'nullable|string',
        ]);

        $sparePart = SparePart::findOrFail($validated['spare_part_id']);

        // Pour une sortie, valider que le stock est suffisant
        if ($validated['type'] === 'sortie' && $sparePart->quantity < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'La quantité en stock est insuffisante pour cette sortie.'])->withInput();
        }

        // Valider que la pièce de rechange appartient bien à la région sélectionnée
        if ($sparePart->region_id != $validated['region_id']) {
            return back()->withErrors(['spare_part_id' => 'La pièce sélectionnée n\'appartient pas à la région indiquée.'])->withInput();
        }

        DB::transaction(function () use ($validated, $sparePart) {
            // Créer le mouvement
            SparePartMovement::create([
                'spare_part_id' => $validated['spare_part_id'],
                'user_id' => $validated['user_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'location' => $validated['location'],
                'notes' => $validated['notes'],
            ]);

            // Mettre à jour la quantité de la pièce de rechange
            if ($validated['type'] === 'entree') {
                $sparePart->quantity += $validated['quantity'];
            } else { // sortie
                $sparePart->quantity -= $validated['quantity'];
            }

            // Mettre à jour la localisation sur la pièce si elle est fournie dans le mouvement
            if (!empty($validated['location'])) {
                $sparePart->location = $validated['location'];
            }

            $sparePart->save();
        });

        return redirect()->route('spare-part-movements.index')
            ->with('success', 'Mouvement de pièce de rechange créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SparePartMovement $sparePartMovement)
    {
        return Inertia::render('Actifs/SparePartMovements/Show', [
            'sparePartMovement' => $sparePartMovement->load(['sparePart', 'user']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SparePartMovement $sparePartMovement)
    {
        return Inertia::render('Actifs/SparePartMovements', [
            'sparePartMovement' => $sparePartMovement->load(['sparePart', 'user']),
            'spareParts' => SparePart::all(['id', 'reference']),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SparePartMovement $sparePartMovement)
    {
        $validated = $request->validateWithBag('updateSparePartMovement', [
            'spare_part_id' => 'required|exists:spare_parts,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:entree,sortie',
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($sparePartMovement, $validated) {
            $originalSparePart = $sparePartMovement->sparePart;
            $originalType = $sparePartMovement->type;
            $originalQuantity = $sparePartMovement->quantity;

            // Annuler l'impact de l'ancien mouvement sur la quantité
            if ($originalType === 'entree') {
                $originalSparePart->quantity -= $originalQuantity;
            } else { // sortie
                $originalSparePart->quantity += $originalQuantity;
            }

            $newSparePart = ($originalSparePart->id == $validated['spare_part_id'])
                ? $originalSparePart
                : SparePart::findOrFail($validated['spare_part_id']);

            // Calculer l'impact du nouveau mouvement
            $quantityChange = $validated['quantity'];
            if ($validated['type'] === 'sortie') {
                $quantityChange = -$quantityChange;
            }

            // Vérifier si le stock est suffisant pour la mise à jour
            // Surtout si on change de pièce ou si la quantité de sortie augmente
            if ($newSparePart->id !== $originalSparePart->id) {
                $originalSparePart->save(); // Sauvegarder l'annulation sur l'ancienne pièce
                if ($newSparePart->quantity + $quantityChange < 0) {
                    throw new \Exception('La quantité en stock est insuffisante pour cette sortie sur la nouvelle pièce.');
                }
            } else {
                if ($originalSparePart->quantity + $quantityChange < 0) {
                    throw new \Exception('La quantité en stock est insuffisante pour cette sortie.');
                }
            }

            // Appliquer le nouvel impact
            $newSparePart->quantity += $quantityChange;
            if (!empty($validated['location'])) {
                $newSparePart->location = $validated['location'];
            }
            $newSparePart->save();

            // Mettre à jour le mouvement lui-même
            $sparePartMovement->update($validated);
        });



        return redirect()->route('spare-part-movements.index')
            ->with('success', 'Mouvement de pièce de rechange mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SparePartMovement $sparePartMovement)
    {
        DB::transaction(function () use ($sparePartMovement) {
            $sparePart = $sparePartMovement->sparePart;
            $type = $sparePartMovement->type;
            $quantity = $sparePartMovement->quantity;

            // Annuler l'effet du mouvement sur la quantité de la pièce
            if ($type === 'entree') {
                $sparePart->quantity -= $quantity;
            } else { // 'sortie'
                $sparePart->quantity += $quantity;
            }
            $sparePart->save();
            $sparePartMovement->delete();
        });
        return redirect()->route('spare-part-movements.index')
            ->with('success', 'Mouvement de pièce de rechange supprimé avec succès.');
    }
}
