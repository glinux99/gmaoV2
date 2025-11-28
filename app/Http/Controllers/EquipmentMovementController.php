<?php

namespace App\Http\Controllers;

use App\Models\EquipmentMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentMovement::with(['equipment', 'user']);

        // Allow filtering by a specific equipment
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', '%' . $search . '%')
                ->orWhere('type', 'like', '%' . $search . '%')
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('equipment', fn ($q) => $q->where('tag', 'like', '%' . $search . '%'));
        }

        return Inertia::render('Actifs/EquipmentMovements', [
            'equipmentMovements' => $query->latest()->paginate(15),
            'filters' => $request->only(['search', 'equipment_id']),
        ]);
    }
}
