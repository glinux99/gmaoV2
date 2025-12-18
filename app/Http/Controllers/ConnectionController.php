<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Seal;
use Illuminate\Http\Request;
use App\Imports\ConnectionsImport;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException as ValidationValidationException;
use League\Config\Exception\ValidationException;

class ConnectionController extends Controller
{
    /**
 * Display a listing of the resource.
     */
public function index(Request $request)
    {
        $query = Connection::query()->with(['region', 'zone', 'meter', 'keypad']);

        // Recherche simple
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        if ($request->has(['user_lat', 'user_lng'])) {
        $lat = (float) $request->user_lat;
        $lng = (float) $request->user_lng;

        // Formule de calcul de distance (Haversine)
        $query->selectRaw("*, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(gps_latitude)) * COS(RADIANS(gps_longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(gps_latitude)))) AS distance", [$lat, $lng, $lat])
              ->orderBy('distance', 'asc');
    }

        return Inertia::render('Tasks/Connections', [
            'connections' => $query->latest()->paginate($request->input('per_page', 30)),
            'filters' => $request->all(['search']),
            'regions' => Region::all(['id', 'designation as name']),
            'zones' => Zone::all(['id', 'title as name']),
            'connectionStatuses' => [
                ['label' => 'Raccordé', 'value' => '5 - Raccordé'],
                ['label' => 'En attente', 'value' => 'pending'],
                ['label' => 'Clôturé', 'value' => 'Clôturé'],
            ],
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

        DB::beginTransaction();
        try {
            \Maatwebsite\Excel\Facades\Excel::import(new ConnectionsImport, $request->file('file'));
            DB::commit();

            return redirect()->route('connections.index')->with('success', 'Import des raccordements terminé avec succès.');

        } catch (ValidationValidationException $e) {
            DB::rollBack();

            $failures = $e;

            // Collecter les messages d'erreur pour les afficher à l'utilisateur
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Ligne " . $failure->row() . ": " . implode(", ", $failure->errors());
            }

            // Loguer l'erreur complète pour le débogage (optionnel)
            Log::error("Erreur de validation d'importation: " . json_encode($errorMessages));

            return redirect()->back()->with([
                'error' => 'Erreur de validation lors de l\'importation. Consultez les messages ci-dessous.',
                'import_errors' => $errorMessages // Passer les erreurs détaillées à la vue
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Loguer l'erreur générique
            return $e;
            Log::error("Erreur générique lors de l'importation: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());

            // Renvoyer un message d'erreur plus convivial
            return redirect()->back()->with('error', 'Erreur lors de l\'importation des raccordements: ' . $e->getMessage());
        }
    }
}
