<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Meter;
use App\Models\Region;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MeterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Meter::query()->with('connection', 'region', 'zone');

        if (request()->has('search')) {
            $search = request('search');
            $query->where('serial_number', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $search . '%')
                  ->orWhere('type', 'like', '%' . $search . '%')
                  ->orWhereHas('connection', fn($q) => $q->where('customer_code', 'like', '%' . $search . '%'))
                  ->orWhere('status', 'like', '%' . $search . '%');
        }

       return Inertia::render('Actifs/Meters', [
        'meters' => Meter::with(['connection', 'region', 'zone'])->paginate(), // Assurez-vous de charger les relations
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
        'regions' => Region::all(),
        // Important : Assurez-vous que vos zones ont un attribut 'region_id'
        'zones' => Zone::all(),
        'filters' => request()->all('search', 'status'),
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
     * Import meters from a file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($data); // Assuming the first row is the header

        foreach ($data as $row) {
            $meterData = array_combine($header, $row);
            Meter::create([
                'serial_number' => $meterData['serial_number'],
                'model' => $meterData['model'] ?? null,
                'manufacturer' => $meterData['manufacturer'] ?? null,
                'type' => $meterData['type'] ?? null,
                'status' => $meterData['status'] ?? 'active',
                'installation_date' => $meterData['installation_date'] ?? null,
                'connection_id' => $meterData['connection_id'] ?? null,
                'is_additional' => $meterData['is_additional'] ?? false,
                'region_id' => $meterData['region_id'] ?? null,
                'zone_id' => $meterData['zone_id'] ?? null,
            ]);
        }

        return redirect()->route('meters.index')->with('success', 'Compteurs importés avec succès.');
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
            Meter::whereIn('id', $validated['meter_ids'])->update([
                'connection_id' => $validated['connection_id'] ?? null,
                'region_id' => $validated['region_id'] ?? null,
                'zone_id' => $validated['zone_id'] ?? null,
            ]);
        });

        return redirect()->route('meters.index')->with('success', 'Compteurs transférés avec succès.');
    }
}
