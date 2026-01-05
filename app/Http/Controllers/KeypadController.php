<?php

namespace App\Http\Controllers;

use App\Models\Connection ;
use App\Models\Keypad;
use App\Models\Meter;
use App\Models\Region;
use App\Models\StockMovement;
use App\Models\Zone;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KeypadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Keypad::query()->with(['connection', 'region', 'zone', 'meter']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('serial_number', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhereHas('connection', fn ($q) => $q->where('customer_code', 'like', "%{$search}%"))
                ->orWhereHas('region', fn ($q) => $q->where('designation', 'like', "%{$search}%"))
                ->orWhereHas('zone', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orWhereHas('meter', fn ($q) => $q->where('serial_number', 'like', "%{$search}%"));
        }

        return Inertia::render('Actifs/Keypads', [
            'keypads' => $query->paginate($request->input('per_page', 10)),
            'filters' => $request->only(['search']),
            'connections' => Connection::all()->map(fn($conn) => [
              'id' => $conn->id,
            'full_name' => $conn->first_name . ' ' . $conn->last_name,
            'customer_code'=> $conn->customer_code,
            'region_id' => $conn->region_id,
            'zone_id' => $conn->zone_id,
            'status' => $conn->status,
            'meter_id' => $conn->meter_id,
            'keypad_id' => $conn->keypad_id
        ]),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
            'meters' => Meter::all(['id', 'serial_number', 'region_id','zone_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Actifs/Keypads/Create', [
            'connections' => Connection::all(['id', 'customer_code']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
            'meters' => Meter::all(['id', 'serial_number']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:keypads,serial_number',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'connection_id' => 'nullable|exists:connections,id',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
            'meter_id' => 'nullable|exists:meters,id',
            'notes' => 'nullable|string',
        ]);

        Keypad::create($validated);

        return redirect()->route('keypads.index')->with('success', 'Clavier créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Keypad $keypad)
    {
        return Inertia::render('Actifs/Keypads/Show', [
            'keypad' => $keypad->load(['connection', 'region', 'zone', 'meter']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Keypad $keypad)
    {
        return Inertia::render('Actifs/Keypads/Edit', [
            'keypad' => $keypad->load(['connection', 'region', 'zone', 'meter']),
            'connections' => Connection::all(['id', 'customer_code']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
            'meters' => Meter::all(['id', 'serial_number']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Keypad $keypad)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:keypads,serial_number,' . $keypad->id,
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'connection_id' => 'nullable|exists:connections,id',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
            'meter_id' => 'nullable|exists:meters,id',
            'notes' => 'nullable|string',
        ]);

        $keypad->update($validated);

        return redirect()->route('keypads.index')->with('success', 'Clavier mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Keypad $keypad)
    {
        $keypad->delete();
        return redirect()->route('keypads.index')->with('success', 'Clavier supprimé avec succès.');
    }

    /**
     * Bulk transfer keypads to a new connection, region, zone, or meter.
     */
    public function bulkTransfer(Request $request)
    {
        $validated = $request->validate([
            'keypad_ids' => 'required|array',
            'keypad_ids.*' => 'exists:keypads,id',
            'connection_id' => 'nullable|exists:connections,id',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
            'meter_id' => 'nullable|exists:meters,id',
        ]);

        DB::transaction(function () use ($validated) {
            $keypads = Keypad::whereIn('id', $validated['keypad_ids'])->get();
            $destinationRegionId = $validated['region_id'] ?? null;

            foreach ($keypads as $keypad) {
                $sourceRegionId = $keypad->region_id;

                // Mettre à jour le clavier
                $keypad->update([
                    'connection_id' => $validated['connection_id'] ?? $keypad->connection_id,
                    'region_id' => $destinationRegionId,
                    'zone_id' => $validated['zone_id'] ?? $keypad->zone_id,
                    'meter_id' => $validated['meter_id'] ?? $keypad->meter_id,
                ]);

                // Enregistrer le mouvement de stock
                StockMovement::create([
                    'movable_type' => Keypad::class,
                    'movable_id' => $keypad->id,
                    'type' => 'transfer',
                    'quantity' => 1, // Toujours 1 pour un article sérialisé
                    'source_region_id' => $sourceRegionId,
                    'destination_region_id' => $destinationRegionId,
                    'user_id' => auth()->id(),
                    'date' => now(),
                    'notes' => 'Transfert en masse depuis la page des claviers.',
                ]);
            }
        });

        return redirect()->route('keypads.index')->with('success', 'Claviers transférés avec succès.');
    }
}
