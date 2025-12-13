<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Seal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;



class ConnectionController extends Controller
{
    /**
 * Display a listing of the resource.
     */
 public function index(Request $request)
 {
 $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
 $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

 $query = Connection::with(['region', 'zone', 'meter', 'keypad', 'seals', 'additionalMeters']);

 $query->where(function ($q) use ($startDate, $endDate) {
 $q->whereBetween('connection_date', [$startDate, $endDate])
 ->orWhereBetween('payment_date', [$startDate, $endDate]);
 });

 if ($search = $request->get('search')) {
 $query->where(function ($q) use ($search) {
 $q->where('customer_code', 'like', "%{$search}%")
 ->orWhere('first_name', 'like', "%{$search}%")
 ->orWhere('last_name', 'like', "%{$search}%")
 ->orWhere('phone_number', 'like', "%{$search}%")
 ->orWhere('status', 'like', "%{$search}%")
 ->orWhereHas('region', fn ($sq) => $sq->where('designation', 'like', "%{$search}%"))
 ->orWhereHas('zone', fn ($sq) => $sq->where('title', 'like', "%{$search}%"));
 });
 }

 $connections = $query->paginate(10);

 return Inertia::render('Tasks/Connections', [
 'connections' => $connections,
 'regions' => Region::all(['id', 'designation']),
 'zones' => Zone::all(['id', 'title']),
 'meters' => Meter::all(['id', 'serial_number']),
 'keypads' => Keypad::all(['id', 'serial_number']),
 'filters' => $request->only(['search', 'start_date', 'end_date']),
 ]);
 }

    /**
 * Show the form for creating a new resource.
     */
 public function create()
 {
 return Inertia::render('Connections/Create', [
 'regions' => Region::all(['id', 'designation']),
 'zones' => Zone::all(['id', 'title']),
 'meters' => Meter::whereNull('connection_id')->get(['id', 'serial_number']), // Only available meters
 'keypads' => Keypad::whereNull('connection_id')->get(['id', 'serial_number']), // Only available keypads
 ]);
 }

    /**
 * Store a newly created resource in storage.
     */
 public function store(Request $request)
 {
 $validated = $request->validate([
 'customer_code' => 'required|string|unique:connections,customer_code',
 'region_id' => 'nullable|exists:regions,id',
 'zone_id' => 'nullable|exists:zones,id',
 'status' => 'required|string',
 'first_name' => 'required|string|max:255',
 'last_name' => 'nullable|string|max:255',
 'phone_number' => 'nullable|string|max:255',
 'secondary_phone_number' => 'nullable|string|max:255',
 'gps_latitude' => 'nullable|numeric',
 'gps_longitude' => 'nullable|numeric',
 'customer_type' => 'nullable|string|max:255',
 'customer_type_details' => 'nullable|string|max:255',
 'commercial_agent_name' => 'nullable|string|max:255',
 'amount_paid' => 'nullable|numeric',
 'payment_number' => 'nullable|string|max:255',
 'payment_voucher_number' => 'nullable|string|max:255',
 'payment_date' => 'nullable|date',
 'is_verified' => 'boolean',
 'connection_type' => 'nullable|string|max:255',
 'connection_date' => 'nullable|date',
 'meter_id' => 'nullable|exists:meters,id',
 'keypad_id' => 'nullable|exists:keypads,id',
 'cable_section' => 'nullable|string|max:255',
 'meter_type_connected' => 'nullable|string|max:255',
 'cable_length' => 'nullable|integer',
 'box_type' => 'nullable|string|max:255',
 'meter_seal_number' => 'nullable|string|max:255',
 'box_seal_number' => 'nullable|string|max:255',
 'phase_number' => 'nullable|string|max:255',
 'amperage' => 'nullable|string|max:255',
 'voltage' => 'nullable|integer',
 'with_ready_box' => 'boolean',
 'tariff' => 'nullable|string|max:255',
 'tariff_index' => 'nullable|string|max:255',
 'pole_number' => 'nullable|string|max:255',
 'distance_to_pole' => 'nullable|integer',
 'needs_small_pole' => 'boolean',
 'bt_poles_installed' => 'nullable|integer',
 'small_poles_installed' => 'nullable|integer',
 'additional_meter_1' => 'nullable|string|max:255',
 'additional_meter_2' => 'nullable|string|max:255',
 'additional_meter_3' => 'nullable|string|max:255',
 'rccm_number' => 'nullable|string|max:255',
 'materials_used' => 'nullable|array',
 'seals' => 'nullable|array', // For creating associated seals
 'seals.*.serial_number' => 'required_with:seals|string|unique:seals,serial_number',
 'seals.*.type' => 'required_with:seals|string',
 'seals.*.status' => 'nullable|string',
 'seals.*.installation_date' => 'nullable|date',
 'additional_meters' => 'nullable|array', // For creating associated additional meters
 'additional_meters.*.serial_number' => 'required_with:additional_meters|string|unique:meters,serial_number',
 'additional_meters.*.model' => 'nullable|string',
 'additional_meters.*.manufacturer' => 'nullable|string',
 'additional_meters.*.type' => 'nullable|string',
 'additional_meters.*.status' => 'nullable|string',
 'additional_meters.*.installation_date' => 'nullable|date',
 ]);

 DB::beginTransaction();
 try {
 $connection = Connection::create($validated);

 // Update meter and keypad connection_id
 if (isset($validated['meter_id'])) {
 Meter::where('id', $validated['meter_id'])->update(['connection_id' => $connection->id]);
 }
 if (isset($validated['keypad_id'])) {
 Keypad::where('id', $validated['keypad_id'])->update(['connection_id' => $connection->id]);
 }

 // Create associated seals
 if (isset($validated['seals'])) {
 foreach ($validated['seals'] as $sealData) {
 $connection->seals()->create($sealData);
 }
 }

 // Create associated additional meters
 if (isset($validated['additional_meters'])) {
 foreach ($validated['additional_meters'] as $meterData) {
 $connection->additionalMeters()->create(array_merge($meterData, ['is_additional' => true]));
 }
 }

 DB::commit();
 return redirect()->route('connections.index')->with('success', 'Raccordement créé avec succès.');
 } catch (\Exception $e) {
 DB::rollBack();
 return redirect()->back()->with('error', 'Erreur lors de la création du raccordement: ' . $e->getMessage());
 }
 }

    /**
 * Display the specified resource.
     */
 public function show(Connection $connection)
 {
 return Inertia::render('Connections/Show', [
 'connection' => $connection->load(['region', 'zone', 'meter', 'keypad', 'seals', 'additionalMeters']),
 ]);
 }

    /**
 * Show the form for editing the specified resource.
     */
 public function edit(Connection $connection)
 {
 return Inertia::render('Connections/Edit', [
 'connection' => $connection->load(['region', 'zone', 'meter', 'keypad', 'seals', 'additionalMeters']),
 'regions' => Region::all(['id', 'designation']),
 'zones' => Zone::all(['id', 'title']),
 'meters' => Meter::whereNull('connection_id')->orWhere('connection_id', $connection->id)->get(['id', 'serial_number']),
 'keypads' => Keypad::whereNull('connection_id')->orWhere('connection_id', $connection->id)->get(['id', 'serial_number']),
 ]);
 }

    /**
 * Update the specified resource in storage.
     */
 public function update(Request $request, Connection $connection)
 {
 $validated = $request->validate([
 'customer_code' => ['required', 'string', Rule::unique('connections')->ignore($connection->id)],
 'region_id' => 'nullable|exists:regions,id',
 'zone_id' => 'nullable|exists:zones,id',
 'status' => 'required|string',
 'first_name' => 'required|string|max:255',
 'last_name' => 'nullable|string|max:255',
 'phone_number' => 'nullable|string|max:255',
 'secondary_phone_number' => 'nullable|string|max:255',
 'gps_latitude' => 'nullable|numeric',
 'gps_longitude' => 'nullable|numeric',
 'customer_type' => 'nullable|string|max:255',
 'customer_type_details' => 'nullable|string|max:255',
 'commercial_agent_name' => 'nullable|string|max:255',
 'amount_paid' => 'nullable|numeric',
 'payment_number' => 'nullable|string|max:255',
 'payment_voucher_number' => 'nullable|string|max:255',
 'payment_date' => 'nullable|date',
 'is_verified' => 'boolean',
 'connection_type' => 'nullable|string|max:255',
 'connection_date' => 'nullable|date',
 'meter_id' => 'nullable|exists:meters,id',
 'keypad_id' => 'nullable|exists:keypads,id',
 'cable_section' => 'nullable|string|max:255',
 'meter_type_connected' => 'nullable|string|max:255',
 'cable_length' => 'nullable|integer',
 'box_type' => 'nullable|string|max:255',
 'meter_seal_number' => 'nullable|string|max:255',
 'box_seal_number' => 'nullable|string|max:255',
 'phase_number' => 'nullable|string|max:255',
 'amperage' => 'nullable|string|max:255',
 'voltage' => 'nullable|integer',
 'with_ready_box' => 'boolean',
 'tariff' => 'nullable|string|max:255',
 'tariff_index' => 'nullable|string|max:255',
 'pole_number' => 'nullable|string|max:255',
 'distance_to_pole' => 'nullable|integer',
 'needs_small_pole' => 'boolean',
 'bt_poles_installed' => 'nullable|integer',
 'small_poles_installed' => 'nullable|integer',
 'additional_meter_1' => 'nullable|string|max:255',
 'additional_meter_2' => 'nullable|string|max:255',
 'additional_meter_3' => 'nullable|string|max:255',
 'rccm_number' => 'nullable|string|max:255',
 'materials_used' => 'nullable|array',
 'seals' => 'nullable|array',
 'seals.*.id' => 'nullable|exists:seals,id',
 'seals.*.serial_number' => ['required_with:seals', 'string', Rule::unique('seals', 'serial_number')->ignore($request->input('seals.*.id'))],
 'seals.*.type' => 'required_with:seals|string',
 'seals.*.status' => 'nullable|string',
 'seals.*.installation_date' => 'nullable|date',
 'additional_meters' => 'nullable|array',
 'additional_meters.*.id' => 'nullable|exists:meters,id',
 'additional_meters.*.serial_number' => ['required_with:additional_meters', 'string', Rule::unique('meters', 'serial_number')->ignore($request->input('additional_meters.*.id'))],
 'additional_meters.*.model' => 'nullable|string',
 'additional_meters.*.manufacturer' => 'nullable|string',
 'additional_meters.*.type' => 'nullable|string',
 'additional_meters.*.status' => 'nullable|string',
 'additional_meters.*.installation_date' => 'nullable|date',
 ]);

 DB::beginTransaction();
 try {
 // Dissociate old meter and keypad if they are changing
 if ($connection->meter_id && $connection->meter_id != $validated['meter_id']) {
 Meter::where('id', $connection->meter_id)->update(['connection_id' => null]);
 }
 if ($connection->keypad_id && $connection->keypad_id != $validated['keypad_id']) {
 Keypad::where('id', $connection->keypad_id)->update(['connection_id' => null]);
 }

 $connection->update($validated);

 // Associate new meter and keypad
 if (isset($validated['meter_id'])) {
 Meter::where('id', $validated['meter_id'])->update(['connection_id' => $connection->id]);
 }
 if (isset($validated['keypad_id'])) {
 Keypad::where('id', $validated['keypad_id'])->update(['connection_id' => $connection->id]);
 }

 // Sync seals
 $currentSealIds = collect($validated['seals'])->pluck('id')->filter()->toArray();
 $connection->seals()->whereNotIn('id', $currentSealIds)->delete(); // Delete removed seals
 foreach ($validated['seals'] as $sealData) {
 if (isset($sealData['id'])) {
 Seal::where('id', $sealData['id'])->update($sealData);
 } else {
 $connection->seals()->create($sealData);
 }
 }

 // Sync additional meters
 $currentAdditionalMeterIds = collect($validated['additional_meters'])->pluck('id')->filter()->toArray();
 $connection->additionalMeters()->whereNotIn('id', $currentAdditionalMeterIds)->delete(); // Delete removed additional meters
 foreach ($validated['additional_meters'] as $meterData) {
 if (isset($meterData['id'])) {
 Meter::where('id', $meterData['id'])->update(array_merge($meterData, ['is_additional' => true]));
 } else {
 $connection->additionalMeters()->create(array_merge($meterData, ['is_additional' => true]));
 }
 }

 DB::commit();
 return redirect()->route('connections.index')->with('success', 'Raccordement mis à jour avec succès.');
 } catch (\Exception $e) {
 DB::rollBack();
 return redirect()->back()->with('error', 'Erreur lors de la mise à jour du raccordement: ' . $e->getMessage());
 }
 }

    /**
 * Remove the specified resource from storage.
     */
 public function destroy(Connection $connection)
 {
 DB::beginTransaction();
 try {
 // Dissociate meter and keypad
 if ($connection->meter_id) {
 Meter::where('id', $connection->meter_id)->update(['connection_id' => null]);
 }
 if ($connection->keypad_id) {
 Keypad::where('id', $connection->keypad_id)->update(['connection_id' => null]);
 }

 // Delete associated seals and additional meters
 $connection->seals()->delete();
 $connection->additionalMeters()->delete();

 $connection->delete();
 DB::commit();
 return redirect()->route('connections.index')->with('success', 'Raccordement supprimé avec succès.');
 } catch (\Exception $e) {
 DB::rollBack();
 return redirect()->back()->with('error', 'Erreur lors de la suppression du raccordement: ' . $e->getMessage());
 }
 }


    /**
     * Import connections from a CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Impossible de lire le fichier importé.');
        }

        $header = fgetcsv($handle); // first line
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'Fichier CSV invalide.');
        }

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (!$data || empty($data['customer_code'])) {
                    continue;
                }

                // Prepare data for creation/update
                $connectionData = [
                    'customer_code' => $data['customer_code'],
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'status' => $data['status'] ?? 'pending', // Default status
                    // Map other fields as necessary
                    'region_id' => Region::where('designation', $data['region'] ?? null)->first()->id ?? null,
                    'zone_id' => Zone::where('title', $data['zone'] ?? null)->first()->id ?? null,
                    // Add more fields from your CSV to the connectionData array
                ];

                // Create or update connection
                Connection::updateOrCreate(
                    ['customer_code' => $connectionData['customer_code']],
                    $connectionData
                );
            }
            DB::commit();
            fclose($handle);
            return redirect()->route('connections.index')->with('success', 'Import des raccordements terminé.');
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->back()->with('error', 'Erreur lors de l\'importation des raccordements: ' . $e->getMessage());
        }
    }
}
