<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Meter;
use App\Models\Region;
use App\Models\Zone;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Imports\MeterImport;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MeterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Meter::with(['connection', 'region', 'zone']);

        // Gestion du tri
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Gestion des filtres
        if ($request->has('filters')) {
            $filters = $request->input('filters');
            if (!empty($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('serial_number', 'like', '%' . $globalFilter . '%')
                      ->orWhere('model', 'like', '%' . $globalFilter . '%')
                      ->orWhere('manufacturer', 'like', '%' . $globalFilter . '%')
                      ->orWhere('type', 'like', '%' . $globalFilter . '%')
                      ->orWhere('status', 'like', '%' . $globalFilter . '%')
                      ->orWhereHas('connection', fn ($subQ) => $subQ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $globalFilter . '%'));
                });
            }
        }

        $perPage = $request->input('rows', 15);

        // Calcul optimisé des stocks par région
        $stockByRegion = Meter::where('meters.status', 'in_stock')
            ->orWhereNull('region_id')
            ->leftJoin('regions', 'meters.region_id', '=', 'regions.id')
            ->select(DB::raw('COALESCE(regions.designation, "Non spécifiée") as name'), DB::raw('count(meters.id) as count'))
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        // Calcul optimisé des stocks par zone pour chaque région
        $stockByZoneData = Meter::where('meters.status', 'in_stock')
            ->whereNotNull('meters.region_id') // On ne peut pas grouper par zone si la région est nulle
            ->leftJoin('regions', 'meters.region_id', '=', 'regions.id')
            ->leftJoin('zones', 'meters.zone_id', '=', 'zones.id')
            ->select(
                'regions.designation as region_name',
                DB::raw('COALESCE(zones.nomenclature, "Hors-zone") as zone_name'),
                DB::raw('count(meters.id) as count')
            )
            ->groupBy('regions.designation', 'zone_name')
            ->orderBy('regions.designation')->orderBy('zone_name')
            ->get();

        $stockByZone = $stockByZoneData->groupBy('region_name')
            ->map(function ($zonesInRegion) {
                return $zonesInRegion->map(fn($item) => ['name' => $item->zone_name, 'count' => $item->count])->values();
            });

        return Inertia::render('Actifs/Meters', [
        'meters' => $query->paginate($perPage)->withQueryString(),
        // 'connections' => Connection::all(),
        // 'regions' => Region::all(),
        // 'zones' => Zone::all(),
        'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name', 'keypad_id', 'zone_id', 'region_id']),
        'regions' => Region::all(['id', 'designation']),
        'zones' => Zone::all(['id', 'nomenclature', 'region_id']),
        'filters' => $request->only(['filters']),
        'queryParams' => $request->all(['sortField', 'sortOrder', 'rows']),
        // Statistiques globales
        'total' => Meter::count(),
        'active' => Meter::where('status', 'active')->count(),
        'in_stock' => Meter::where('status', 'in_stock')->count(),
        'faulty' => Meter::where('status', 'faulty')->count(),
        'stockByRegion' => $stockByRegion, // Nouvelle prop
        'stockByZone' => $stockByZone,     // Nouvelle prop
    ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Actifs/Meters/Create', [
            'connections' => Connection::all(['id', 'customer_code']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:meters,serial_number',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'connection_id' => 'nullable|exists:connections,id',
            'is_additional' => 'boolean',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        Meter::create($validated);

        return redirect()->route('meters.index')->with('success', 'Compteur créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Meter $meter)
    {
        return Inertia::render('Actifs/Meters/Show', [
            'meter' => $meter->load('connection'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Meter $meter)
    {
        return Inertia::render('Actifs/Meters/Edit', [
            'meter' => $meter->load('connection'),
            'connections' => Connection::all(['id', 'customer_code']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meter $meter)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:meters,serial_number,' . $meter->id,
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'connection_id' => 'nullable|exists:connections,id',
            'is_additional' => 'boolean',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $meter->update($validated);

        return redirect()->route('meters.index')->with('success', 'Compteur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meter $meter)
    {
        $meter->delete();
        return redirect()->route('meters.index')->with('success', 'Compteur supprimé avec succès.');
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:meters,id',
        ]);

        DB::transaction(function () use ($validated) {
            // Vous pouvez ajouter ici la logique pour supprimer les relations (ex: StockMovement) si nécessaire
            Meter::whereIn('id', $validated['ids'])->delete();
        });

        return redirect()->route('meters.index')->with('success', 'Les compteurs sélectionnés ont été supprimés avec succès.');
    }



    /**
     * Import meters from a file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        $destinationRegionId = $request->input('region_id');

        $import = new MeterImport($destinationRegionId);

        try {
            DB::transaction(function () use ($import, $request) {
                \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
            });

            return redirect()->route('meters.index')->with([
                'success' => 'Importation terminée avec succès.',
                'import_summary' => $import->getSummary()
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Gérer les erreurs de validation
            return $e;
            return back()->with('import_errors', $e->failures());
        } catch (\Exception $e) {
               return $e;
            // Gérer les autres erreurs
            return back()->with('error', "Une erreur est survenue: " . $e->getMessage());
        }
    }

    /**
     * Bulk transfer meters to a new connection, region, or zone.
     */
    public function bulkTransfer(Request $request)
    {
        $validated = $request->validate([
            'meter_ids' => 'required|array',
            'meter_ids.*' => 'exists:meters,id',
            'connection_id' => 'nullable|exists:connections,id',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        DB::transaction(function () use ($validated) {
            $meters = Meter::whereIn('id', $validated['meter_ids'])->get();
            $destinationRegionId = $validated['region_id'] ?? null;

            foreach ($meters as $meter) {
                $sourceRegionId = $meter->region_id;

                // Mettre à jour le compteur
                $meter->update([
                    'connection_id' => $validated['connection_id'] ?? $meter->connection_id,
                    'region_id' => $destinationRegionId,
                    'zone_id' => $validated['zone_id'] ?? $meter->zone_id,
                ]);

                // Enregistrer le mouvement de stock
                StockMovement::create([
                    'movable_type' => Meter::class,
                    'movable_id' => $meter->id,
                    'type' => 'transfer',
                    'quantity' => 1, // Toujours 1 pour un article sérialisé
                    'source_region_id' => $sourceRegionId,
                    'destination_region_id' => $destinationRegionId,
                    'user_id' => auth()->id(),
                    'date' => now(),
                    'notes' => 'Transfert en masse depuis la page des compteurs.',
                ]);
            }
        });

        return redirect()->route('meters.index')->with('success', 'Compteurs transférés avec succès.');
    }
}
