<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function index()
    {

        $query = Equipment::with(['equipmentType', 'region', 'user', 'parent', 'characteristics']);

        if (request()->has('search')) {
            $search = request('search');
            $query->where('brand', 'like', '%' . $search . '%')
                ->orWhere('model', 'like', '%' . $search . '%')
                ->orWhere('serial_number', 'like', '%' . $search . '%')
                ->orWhereHas('equipmentType', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('region', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        return Inertia::render('Actifs/Equipments', [
            'equipments' => $query->paginate(10),
            'filters' => request()->only(['search']),
            'equipmentTypes' => EquipmentType::all(),
            'regions' => Region::get(),
            'users' => User::all(['id', 'name']),
            'parentEquipments' => $this->getEquipments()->load('characteristics'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag' => 'nullable|string|max:255|unique:equipment,tag',
            'designation' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:equipment,serial_number',
            'status' => 'required|in:en service,en panne,en maintenance,hors service,en stock',
            'location' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0|required_if:status,en stock',
            'purchase_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'region_id' => 'nullable|exists:regions,id',
            'parent_id' => 'nullable|exists:equipment,id',
            'characteristics' => 'nullable|array',
            'characteristics.*.name' => 'required_with:characteristics|string',
            'characteristics.*.value' => 'nullable|string',
            'child_quantity' => 'nullable|integer|min:1', // Quantité demandée pour un enfant
        ]);

        DB::transaction(function () use ($validated, $request) {
            $equipmentData = $request->except(['child_quantity', 'characteristics']);
            $equipmentData['user_id'] = Auth::id();

            // Si l'équipement a un parent
            if (!empty($validated['parent_id'])) {
                $parent = Equipment::findOrFail($validated['parent_id']);
                $requestedQuantity = $validated['child_quantity'] ?? 1;

                if ($parent->quantity < $requestedQuantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'child_quantity' => 'La quantité du parent est insuffisante.',
                    ]);
                }

                // Générer un tag unique pour l'enfant
                $baseTag = $parent->tag ?? $parent->designation ?? 'equip';
                $i = 1;
                do {
                    $equipmentData['tag'] = $baseTag . '-' . ($parent->children()->count() + $i);
                } while (Equipment::where('tag', $equipmentData['tag'])->exists() && $i++);
                $equipmentData['user_id'] = Auth::id(); // Ensure user_id is set for child equipment

                $parent->decrement('quantity', $requestedQuantity);
                $equipmentData['quantity'] = $requestedQuantity;
            }

            $equipment = Equipment::create($equipmentData);

            if (!empty($validated['characteristics']) && is_array($validated['characteristics'])) {
                $equipment->characteristics()->createMany($validated['characteristics']);
            }

            // Log creation movement
            $equipment->movements()->create([
                'user_id' => Auth::id(),
                'type' => 'creation',
                'description' => 'Équipement créé avec le statut : ' . $equipment->status,
            ]);
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement créé avec succès.');
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'tag' => 'nullable|string|max:255|unique:equipment,tag,' . $equipment->id,
            'designation' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:equipment,serial_number,' . $equipment->id,
            'status' => 'required|in:en service,en panne,en maintenance,hors service,en stock',
            'location' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0|required_if:status,en stock',
            'purchase_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'region_id' => 'nullable|exists:regions,id',
            'parent_id' => 'nullable|exists:equipment,id',
            'characteristics' => 'nullable|array',
            'characteristics.*.id' => 'nullable|integer',
            'characteristics.*.name' => 'required_with:characteristics|string',
            'characteristics.*.value' => 'nullable|string',
        ]);

        DB::transaction(function () use ($equipment, $validated) {
            $originalStatus = $equipment->status;
            $originalParentId = $equipment->parent_id;
            $originalQuantity = $equipment->quantity;

            // Si le parent change, ajuster les quantités des anciens et nouveaux parents
            if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== $originalParentId) {
                // Rétablir la quantité de l'ancien parent
                if ($originalParentId) {
                    Equipment::find($originalParentId)->increment('quantity', $originalQuantity);
                }
                // Décrémenter la quantité du nouveau parent
                if ($validated['parent_id']) {
                    Equipment::find($validated['parent_id'])->decrement('quantity', $validated['quantity'] ?? $originalQuantity);
                }
            }
            $equipment->update($validated);

            if ($originalStatus !== $validated['status']) {
                $equipment->movements()->create([
                    'user_id' => Auth::id(),
                    'type' => 'changement_statut',
                    'description' => "Statut changé de '{$originalStatus}' à '{$validated['status']}'.",
                ]);
            }

            if (!empty($validated['characteristics'])) {
                foreach ($validated['characteristics'] as $charData) {
                    $equipment->characteristics()->updateOrCreate(
                        ['id' => $charData['id'] ?? null],
                        ['name' => $charData['name'], 'value' => $charData['value']]
                    );
                }
            }
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement mis à jour avec succès.');
    }

    public function destroy(Equipment $equipment)
    {
        DB::transaction(function () use ($equipment) {
            // Log deletion movement before deleting
            $equipment->movements()->create([
                'user_id' => Auth::id(),
                'type' => 'suppression',
                'description' => 'Équipement ' . $equipment->tag . ' supprimé.',
            ]);
            $equipment->delete();
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement supprimé avec succès.');
    }

    public function getEquipments()
    {
        return Equipment::all();
    }
}
