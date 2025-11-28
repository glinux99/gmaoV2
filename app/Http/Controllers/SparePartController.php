<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\Label;
use App\Models\Region;
use App\Models\SparePartCharacteristic;
use App\Models\Unity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SparePartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SparePart::with(['label', 'user', 'sparePartCharacteristics.labelCharacteristic', 'region', 'unity']);

        if ($request->has('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%')
                ->orWhere('location', 'like', '%' . $request->search . '%')
                ->orWhereHas('label', function ($q) use ($request) {
                    $q->where('designation', 'like', '%' . $request->search . '%');
                });
        }

        return Inertia::render('Actifs/SpareParts', [
            'spareParts' => $query->paginate(10), // Changed from labels to spareParts
            'labels' => Label::with('labelCharacteristics')->get(), // To select a label for the spare part
            'users' => User::all(['id', 'name']), // Pass users for the responsible person dropdown,
            'regions' => Region::get(), // To select a region for the spare part,
            'unities' => Unity::get(), // To select a unity for the spare part,
            'sparePartCharacteristics' => [], // Existing characteristics for the spare part
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Actifs/SpareParts', [
            'labels' => Label::with('labelCharacteristics')->get(),
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
        if($request['user_id']==null){
            $request['user_id'] = auth()->user()->id;
        }
        $validated = $request->validate([
            'reference' => 'required|string|max:255|unique:spare_parts',
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'label_id' => 'required|exists:labels,id',
            'region_id' => 'nullable|exists:regions,id',
            'unity_id' => 'nullable|exists:unities,id',
            'user_id' => 'nullable|exists:users,id',
            'characteristic_values' => 'nullable|array',
        ]);

        $sparePart = SparePart::create([
            'reference' => $validated['reference'],
            'quantity' => $validated['quantity'],
            'min_quantity' => $validated['min_quantity'],
            'location' => $validated['location'],
            'label_id' => $validated['label_id'],
            'region_id' => $validated['region_id'] ?? null,
            'unity_id' => $validated['unity_id'] ?? null,
            'user_id' => $validated['user_id'],
        ]);

        if (isset($validated['characteristic_values'])) {
            foreach ($validated['characteristic_values'] as $labelCharacteristicId => $value) {
                if ($value !== null) { // Only store if a value is provided
                    SparePartCharacteristic::create([
                        'spare_part_id' => $sparePart->id,
                        'label_characteristic_id' => $labelCharacteristicId,
                        'value' => $value,
                    ]);
                }
            }
        }

        return redirect()->route('spare-parts.index')->with('success', 'Pièce de rechange créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SparePart $SparePart)
    {
        // Not typically used for Inertia CRUD, but can be implemented if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SparePart $SparePart)
    {
        return Inertia::render('Configurations/SpareParts', [
            'sparePart' => $SparePart->load(['label', 'sparePartCharacteristics.labelCharacteristic']),
            'labels' => Label::with('labelCharacteristics')->get(),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SparePart $SparePart)
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:255|unique:spare_parts,reference,' . $SparePart->id,
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'label_id' => 'required|exists:labels,id',
            'region_id' => 'nullable|exists:regions,id',
            'unity_id' => 'nullable|exists:unities,id',
            'user_id' => 'required|exists:users,id',
            'characteristic_values' => 'nullable|array',
        ]);

        $SparePart->update([
            'reference' => $validated['reference'],
            'quantity' => $validated['quantity'],
            'min_quantity' => $validated['min_quantity'],
            'location' => $validated['location'],
            'label_id' => $validated['label_id'],
            'region_id' => $validated['region_id'] ?? null,
            'unity_id' => $validated['unity_id'] ?? null,
            'user_id' => $validated['user_id'],
        ]);

        // Update or create spare part characteristics
        $SparePart->sparePartCharacteristics()->delete(); // Remove existing characteristics
        if (isset($validated['characteristic_values'])) {
            foreach ($validated['characteristic_values'] as $labelCharacteristicId => $value) {
                if ($value !== null) {
                    SparePartCharacteristic::create([
                        'spare_part_id' => $SparePart->id,
                        'label_characteristic_id' => $labelCharacteristicId,
                        'value' => $value,
                    ]);
                }
            }
        }

        return redirect()->route('spare-parts.index')->with('success', 'Pièce de rechange mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SparePart $SparePart)
    {
        $SparePart->sparePartCharacteristics()->delete();
        $SparePart->delete();
        return redirect()->route('spare-parts.index')->with('success', 'Pièce de rechange supprimée avec succès.');
    }
}
