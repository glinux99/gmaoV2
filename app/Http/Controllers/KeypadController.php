<?php

namespace App\Http\Controllers;

use App\Models\Connection ;
use App\Models\Keypad;
use App\Models\Meter;
use App\Models\Region;
use App\Models\StockMovement;
use App\Models\Zone;

use App\Imports\KeypadImport;
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
        $query = Keypad::with(['region', 'zone', 'meter', 'connection'])->select('keypads.*');

        // Gestion du tri
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder') === 'asc' ? 'asc' : 'desc';

        // Tri sur des relations
        if ($sortField === 'connection.full_name') {
            $query->leftJoin('connections', 'keypads.connection_id', '=', 'connections.id')
                  ->orderBy(DB::raw("CONCAT(connections.first_name, ' ', connections.last_name)"), $sortOrder);
        } elseif ($sortField === 'meter.serial_number') {
            $query->leftJoin('meters', 'keypads.meter_id', '=', 'meters.id')
                  ->orderBy('meters.serial_number', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        // Gestion des filtres
        if ($request->has('filters')) {
            $filters = $request->input('filters');

            // Filtre global
            if (!empty($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('keypads.serial_number', 'like', '%' . $globalFilter . '%')
                      ->orWhere('keypads.model', 'like', '%' . $globalFilter . '%')
                      ->orWhere('keypads.manufacturer', 'like', '%' . $globalFilter . '%')
                      ->orWhere('keypads.type', 'like', '%' . $globalFilter . '%')
                      ->orWhere('keypads.status', 'like', '%' . $globalFilter . '%')
                      ->orWhereHas('connection', fn ($subQ) => $subQ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $globalFilter . '%'))
                      ->orWhereHas('meter', fn ($subQ) => $subQ->where('serial_number', 'like', '%' . $globalFilter . '%'));
                });
            }

            // Filtres spécifiques
            if (!empty($filters['serial_number']['constraints'][0]['value'])) {
                $query->where('keypads.serial_number', 'like', $filters['serial_number']['constraints'][0]['value'] . '%');
            }
            if (!empty($filters['type']['value'])) {
                $query->where('keypads.type', $filters['type']['value']);
            }
            if (!empty($filters['status']['value'])) {
                $query->where('keypads.status', $filters['status']['value']);
            }
            if (!empty($filters['connection.full_name']['constraints'][0]['value'])) {
                $query->whereHas('connection', fn ($q) => $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $filters['connection.full_name']['constraints'][0]['value'] . '%'));
            }
            if (!empty($filters['meter.serial_number']['constraints'][0]['value'])) {
                $query->whereHas('meter', fn ($q) => $q->where('serial_number', 'like', $filters['meter.serial_number']['constraints'][0]['value'] . '%'));
            }
        }

        $perPage = $request->input('rows', 15);

        // Calcul optimisé des stocks par région
        $stockByRegion = Keypad::where('keypads.status', 'available')
            ->orWhereNull('region_id')
            ->leftJoin('regions', 'keypads.region_id', '=', 'regions.id')
            ->select(DB::raw('COALESCE(regions.designation, "Non spécifiée") as name'), DB::raw('count(keypads.id) as count'))
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        // Calcul optimisé des stocks par zone pour chaque région
        $stockByZoneData = Keypad::where('keypads.status', 'available')
            ->whereNotNull('keypads.region_id') // On ne peut pas grouper par zone si la région est nulle
            ->leftJoin('regions', 'keypads.region_id', '=', 'regions.id')
            ->leftJoin('zones', 'keypads.zone_id', '=', 'zones.id')
            ->select(
                'regions.designation as region_name',
                DB::raw('COALESCE(zones.nomenclature, "Hors-zone") as zone_name'),
                DB::raw('count(keypads.id) as count')
            )
            ->groupBy('regions.designation', 'zone_name')
            ->orderBy('regions.designation')->orderBy('zone_name')
            ->get();

        $stockByZone = $stockByZoneData->groupBy('region_name')
            ->map(function ($zonesInRegion) {
                return $zonesInRegion->map(fn($item) => ['name' => $item->zone_name, 'count' => $item->count])->values();
            });

        return Inertia::render('Actifs/Keypads', [
            'keypads' => $query->paginate($perPage)->withQueryString(),
             'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name', 'keypad_id', 'zone_id', 'region_id']),
        'regions' => Region::all(['id', 'designation']),
        'zones' => Zone::all(['id', 'nomenclature', 'region_id']),
            'meters' => Meter::all(['id', 'serial_number']),
            'filters' => $request->only(['filters']),
            'queryParams' => $request->all(['sortField', 'sortOrder', 'rows']),
            'installed' => Keypad::where('status', 'installed')->count(),
            'available' => Keypad::where('status', 'available')->count(),
            'faulty' => Keypad::where('status', 'faulty')->count(),
            'total' => Keypad::count(),
            'stockByRegion' => $stockByRegion,
            'stockByZone' => $stockByZone,
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
     * Import keypads from a file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        $destinationRegionId = $request->input('region_id');

        $import = new KeypadImport($destinationRegionId);

        try {
            DB::transaction(function () use ($import, $request) {
                \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
            });

            return redirect()->route('keypads.index')->with([
                'success' => 'Importation des claviers terminée.',
                'import_summary' => $import->getSummary()
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Gérer les erreurs de validation du fichier Excel
            return back()->with('import_errors', $e->failures());
        } catch (\Exception $e) {
            // Gérer les autres erreurs inattendues
            return back()->with('error', "Une erreur est survenue: " . $e->getMessage());
        }
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
