<?php

namespace App\Http\Controllers;

use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentTypeController extends Controller
{
    public function index()
    {
        $query = EquipmentType::query();

        if (request()->has('search')) {
            $search = request('search');
            $query->where('name', 'like', '%' . $search . '%');
        }

        return Inertia::render('Actifs/EquipmentTypes', [
            'equipmentTypes' => $query->paginate(10),
            'filters' => request()->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_types,name',
            'description' => 'nullable|string',
        ]);

        EquipmentType::create($validated);

        return redirect()->back()->with('success', 'Type d\'équipement créé avec succès.');
    }

    public function update(Request $request, EquipmentType $equipmentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_types,name,' . $equipmentType->id,
            'description' => 'nullable|string',
        ]);

        $equipmentType->update($validated);

        return redirect()->back()->with('success', 'Type d\'équipement mis à jour avec succès.');
    }

    public function destroy(EquipmentType $equipmentType)
    {
        // Optional: Check if the type is being used by any equipment before deleting
        if ($equipmentType->equipments()->exists()) {
            return redirect()->back()->with('error', 'Ce type est utilisé et ne peut pas être supprimé.');
        }

        $equipmentType->delete();

        return redirect()->back()->with('success', 'Type d\'équipement supprimé avec succès.');
    }
}
