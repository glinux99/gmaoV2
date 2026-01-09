<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Meter;
use App\Models\Keypad;
use App\Models\Seal;
use App\Models\SparePart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ConnectionController extends Controller
{
    /**
     * Liste des raccordements avec filtres et recherche.
     */
    public function index(Request $request)
    {
        $query = Connection::query()->with(['region', 'zone', 'meter', 'keypad']);

        if ($request->filled('search')) {
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
            $query->selectRaw("*, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(gps_latitude)) * COS(RADIANS(gps_longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(gps_latitude)))) AS distance", [$lat, $lng, $lat])
                  ->orderBy('distance', 'asc');
        } else {
            $query->latest();
        }

        $allParts = SparePart::with('region:id,designation')->get();
        $sparePartsByRef = $allParts->groupBy('id')->map(function ($parts, $ref) {
            return [
                'id' => $parts->first()->id,
                'reference' => $ref,
                'stocks_by_region' => $parts->pluck('quantity', 'region_id'),
            ];
        })->values();

        return Inertia::render('Tasks/Connections', [
            'connections' => $query->paginate($request->input('per_page', 30)),
            'filters' => $request->all(['search']),
            'regions' => Region::all(['id', 'designation as name']),
            'zones' => Zone::all(['id', 'title as name']),
            'meters' => Meter::whereNull('connection_id')->get(['id', 'serial_number as name']),
            'keypads' => Keypad::whereNull('connection_id')->get(['id', 'serial_number as name']),
            'spareParts' => $sparePartsByRef,
            'connectionStatuses' => [
                ['label' => 'Raccordé', 'value' => '5 - Raccordé'],
                ['label' => 'En attente', 'value' => 'pending'],
                ['label' => 'Clôturé', 'value' => 'Clôturé'],
            ],
        ]);
    }

    /**
     * Enregistrement d'un nouveau raccordement.
     */
    public function store(Request $request)
    {
        $rules = $this->getValidationRules();
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Extraction des données pour la table Connection uniquement
            $relationFields = ['seals', 'additional_meters', 'spare_parts_used', 'meter_id', 'keypad_id', 'cable_section'];
            $connection = Connection::create(Arr::except($validated, $relationFields));

            // 1. Liaison des équipements (Meters / Keypads)
            if (!empty($validated['meter_id'])) {
                Meter::where('id', $validated['meter_id'])->update(['connection_id' => $connection->id]);
            }
            if (!empty($validated['keypad_id'])) {
                Keypad::where('id', $validated['keypad_id'])->update(['connection_id' => $connection->id]);
            }

            // 2. Gestion des scellés (Seals)
            if (!empty($validated['seals'])) {
                foreach ($validated['seals'] as $seal) {
                    $connection->seals()->create($seal);
                }
            }

            // 3. Gestion des compteurs additionnels
            if (!empty($validated['additional_meters'])) {
                foreach ($validated['additional_meters'] as $aMeter) {
                    $connection->additionalMeters()->create(array_merge($aMeter, ['is_additional' => true]));
                }
            }

            // 4. Gestion du stock (Spare Parts)
            $this->syncSpareParts($connection, $validated);

            DB::commit();
            return redirect()->route('connections.index')->with('success', 'Raccordement enregistré avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $e;
            Log::error("Erreur Store Connection: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur critique: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Mise à jour d'un raccordement existant.
     */
    public function update(Request $request, Connection $connection)
    {
        $rules = $this->getValidationRules($connection->id);
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // Libérer les anciens équipements
            Meter::where('connection_id', $connection->id)->update(['connection_id' => null]);
            Keypad::where('connection_id', $connection->id)->update(['connection_id' => null]);

            // Update Connection
            $relationFields = ['seals', 'additional_meters', 'spare_parts_used', 'meter_id', 'keypad_id'];
            $connection->update(Arr::except($validated, $relationFields));

            // Relier les nouveaux équipements
            if (!empty($validated['meter_id'])) {
                Meter::where('id', $validated['meter_id'])->update(['connection_id' => $connection->id]);
            }
            if (!empty($validated['keypad_id'])) {
                Keypad::where('id', $validated['keypad_id'])->update(['connection_id' => $connection->id]);
            }

            $this->syncSpareParts($connection, $validated);

            DB::commit();
            return redirect()->route('connections.index')->with('success', 'Mise à jour réussie.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Définition de toutes les règles de validation.
     */
    private function getValidationRules($id = null)
    {
        return [
            // Identifiants & Localisation
            'customer_code'          => ['required', 'string', Rule::unique('connections')->ignore($id)],
            'region_id'              => 'required|exists:regions,id',
            'zone_id'                => 'nullable|exists:zones,id',
            'gps_latitude'           => 'nullable|numeric|between:-90,90',
            'gps_longitude'          => 'nullable|numeric|between:-180,180',

            // Informations Client
            'status'                 => 'required|string|max:50',
            'first_name'             => 'required|string|max:255',
            'last_name'              => 'nullable|string|max:255',
            'phone_number'           => 'nullable|string|max:20',
            'secondary_phone_number' => 'nullable|string|max:20',
            'customer_type'          => 'nullable|string|in:individual,business,other',
            'customer_type_details'  => 'nullable|string|max:255',
            'rccm_number'            => 'nullable|string|max:100',

            // Paiement
            'amount_paid'            => 'nullable|numeric|min:0',
            'payment_number'         => 'nullable|string|max:100',
            'payment_voucher_number' => 'nullable|string|max:100',
            'payment_date'           => 'nullable|date',
            'is_verified'            => 'boolean',

            // Technique Raccordement
            'connection_type'        => 'nullable|string|max:100',
            'connection_date'        => 'nullable|date',
            'cable_section'          => 'nullable|string|max:50',
            'cable_length'           => 'nullable|integer|min:0',
            'box_type'               => 'nullable|string|max:100',
            'meter_type_connected'   => 'nullable|string|max:100',
            'phase_number'           => 'nullable|integer|in:1,3',
            'amperage'               => 'nullable|string|max:50',
            'voltage'                => 'nullable|integer|min:0',
            'with_ready_box'         => 'boolean',
            'tariff'                 => 'nullable|string|max:50',
            'tariff_index'           => 'nullable|string|max:50',

            // Réseau & Poteaux
            'pole_number'            => 'nullable|string|max:100',
            'distance_to_pole'       => 'nullable|integer|min:0',
            'needs_small_pole'       => 'boolean',
            'bt_poles_installed'     => 'nullable|integer|min:0',
            'small_poles_installed'  => 'nullable|integer|min:0',

            // Scellés
            'meter_seal_number'      => 'nullable|string|max:100',
            'box_seal_number'        => 'nullable|string|max:100',

            // Équipements (Relations)
            'meter_id'               => 'nullable|exists:meters,id',
            'keypad_id'              => 'nullable|exists:keypads,id',

            // Tableaux de données liées
            'spare_parts_used'       => 'nullable|array',
            'spare_parts_used.*.id'  => 'required_with:spare_parts_used|exists:spare_parts,id',
            'spare_parts_used.*.quantity' => 'required_with:spare_parts_used|integer|min:1',

            'seals'                  => 'nullable|array',
            'seals.*.serial_number'  => 'required_with:seals|string|max:255',
            'seals.*.type'           => 'required_with:seals|string',

            'additional_meters'      => 'nullable|array',
            'additional_meters.*.serial_number' => 'required_with:additional_meters|string|max:255',
        ];
    }

    /**
     * Synchronisation des pièces détachées avec gestion stricte du stock par région.
     */
    private function syncSpareParts(Connection $connection, array $data)
    {
        $regionId = $data['region_id'] ?? $connection->region_id;
  if (!$connection->wasRecentlyCreated) {
            foreach ($connection->sparePartConnections as $spc) {
                SparePart::where('id', $spc->spare_part_id)->increment('quantity', $spc->quantity_used);
            }
            $connection->sparePartConnections()->delete();
        }


        $partsUsed = $data['spare_parts_used'] ?? [];
        foreach ($partsUsed as $partData) {
            $part = SparePart::where('id', $partData['id'])->where('region_id', $regionId)->first();

            if (!$part) {
                throw new \Exception("La pièce ID {$partData['id']} n'existe pas dans cette région.");
            }

            if ($part->quantity < $partData['quantity']) {
                throw new \Exception("Stock insuffisant pour {$part->reference} (Dispo: {$part->quantity}).");
            }

            $connection->sparePartConnections()->create([
                'spare_part_id' => $part->id,
                'type' => 'used',
                'quantity_used' => $partData['quantity']
            ]);

            $part->decrement('quantity', $partData['quantity']);
        }
    }

    public function destroy(Connection $connection)
    {
        DB::beginTransaction();
        try {
            Meter::where('connection_id', $connection->id)->update(['connection_id' => null]);
            Keypad::where('connection_id', $connection->id)->update(['connection_id' => null]);

            // Rendre le stock avant suppression
            foreach ($connection->sparePartConnections as $spc) {
                SparePart::where('id', $spc->spare_part_id)->increment('quantity', $spc->quantity_used);
            }

            $connection->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Raccordement supprimé et stock rendu.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
