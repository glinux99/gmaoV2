<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\InterventionRequest;
use App\Models\Region;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;

class InterventionRequestController extends Controller
{
    // On centralise les listes pour éviter les répétitions
    protected function getConstants()
    {
        return [
            'interventionReasons' => [
                'Dépannage Réseau Urgent', 'Réparation Éclairage Public', 'Entretien Réseau Planifié',
                'Incident Majeur Réseau', 'Support Achat MobileMoney', 'Support Achat Token Impossible',
                'Aide Recharge (Sans clavier)', 'Élagage Réseau', 'Réparation Chute de Tension',
                'Coupure Individuelle (CI)', 'CI Équipement Client', 'CI Équipement Virunga',
                'CI Vol de Câble', 'Dépannage Clavier Client', 'Réparation Compteur Limité',
                'Rétablissement Déconnexion', 'Déplacement Câble (Planifié)', 'Déplacement Poteau (Planifié)',
                'Reconnexion Client', 'Support Envoi Formulaire', 'Intervention Non-Classifiée',
            ],
            'statuses' => ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'],
            'technicalComplexities' => ['Pas complexe', 'Peu complexe', 'Moyennement complexe', 'Très complexe'],
            'categories' => ['Réseau', 'Support Technique', 'Client', 'Support / Autres'],
            'priorities' => ['Faible', 'Moyenne', 'Élevée', 'Urgent'],
        ];
    }

    public function index(Request $request)
    {
        $query = InterventionRequest::query()
            ->with(['requestedByUser:id,name', 'requestedByConnection:id,customer_code,first_name,last_name', 'assignedToUser:id,name', 'region:id,designation', 'zone:id,title']);

        // --- FILTRES ---
        $query->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status))
              ->when($request->region_id && $request->region_id !== 'all', fn($q) => $q->where('region_id', $request->region_id))
              ->when($request->zone_id && $request->zone_id !== 'all', fn($q) => $q->where('zone_id', $request->zone_id))
              ->when($request->priority && $request->priority !== 'all', fn($q) => $q->where('priority', $request->priority));

        // --- RECHERCHE ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('requestedByConnection', fn($sq) =>
                        $sq->where('customer_code', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                    );
            });
        }

        // --- TRI PAR PROXIMITÉ (GPS) ---
        $isSortingByDistance = false;
        if ($request->has(['user_lat', 'user_lng']) && $request->user_lat != null) {
            $lat = (float) $request->user_lat;
            $lng = (float) $request->user_lng;
            $isSortingByDistance = true;

            $query->selectRaw("*, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(gps_latitude)) * COS(RADIANS(gps_longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(gps_latitude)))) AS distance", [$lat, $lng, $lat])
                  ->orderBy('distance', 'asc');
        }

        if (!$isSortingByDistance) {
            $query->latest();
        }

        return inertia('Tasks/Interventions', array_merge([
            'interventionRequests' => $query->paginate($request->input('per_page', 15)),
            'filters' => $request->all(['search', 'status', 'region_id', 'zone_id']),
            'users' => User::all(['id', 'name']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
            'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name', 'gps_latitude', 'gps_longitude','zone_id','region_id']),
        ], $this->getConstants()));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,assigned,in_progress,completed,cancelled',
            'requested_by_user_id' => 'nullable|exists:users,id',
            'requested_by_connection_id' => 'nullable|exists:connections,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'region_id' => 'nullable|exists:regions,id',
            'zone_id' => 'nullable|exists:zones,id',
            'intervention_reason' => 'nullable|string',
            'category' => 'nullable|string',
            'technical_complexity' => 'nullable|string',
            'min_time_hours' => 'nullable|numeric|min:0',
            'max_time_hours' => 'nullable|numeric|min:0',
            'priority' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
            'is_validated' => 'boolean',
        ]);

        // Valeur par défaut si non fournie
        $validated['is_validated'] = $request->input('is_validated', false);
        $validated['status'] = $request->input('status', 'pending');

        InterventionRequest::create($validated);

        return Redirect::route('interventions.index')->with('success', 'Demande créée.');
    }

   public function update(Request $request, InterventionRequest $intervention)
{
    // Note: J'ai changé le nom de la variable en $intervention pour plus de clarté
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'status' => 'required|in:pending,assigned,in_progress,completed,cancelled', // Validation plus stricte
        'description' => 'nullable|string',
        'requested_by_user_id' => 'nullable|exists:users,id',
        'requested_by_connection_id' => 'nullable|exists:connections,id',
        'assigned_to_user_id' => 'nullable|exists:users,id',
        'region_id' => 'nullable|exists:regions,id',
        'zone_id' => 'nullable|exists:zones,id',
        'intervention_reason' => 'nullable|string',
        'category' => 'nullable|string',
        'technical_complexity' => 'nullable|string',
        'min_time_hours' => 'nullable|numeric|min:0',
        'max_time_hours' => 'nullable|numeric|min:0',
        'priority' => 'nullable|string',
        'scheduled_date' => 'nullable|date',
        'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
        'resolution_notes' => 'nullable|string',
        'gps_latitude' => 'nullable|numeric',
        'gps_longitude' => 'nullable|numeric',
        'is_validated' => 'boolean',
    ]);

    // Mise à jour de l'instance
    $intervention->update($validated);

    // IMPORTANT : Supprimez le "return $validated" qui bloquait Inertia
    return Redirect::route('interventions.index')->with('success', 'Mise à jour réussie.');
}

    public function assign(Request $request, InterventionRequest $intervention)
    {
        $validated = $request->validate([
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'status' => 'nullable', // Force le statut à "assigned"
        ]);

        $intervention->update($validated);

        return Redirect::route('interventions.index')->with('success', 'Demande assignée avec succès.');
    }


    public function destroy(InterventionRequest $interventionRequest)
    {
        $interventionRequest->delete();
        return Redirect::back()->with('success', 'Supprimé.');
    }

    public function cancel(InterventionRequest $intervention)
    {
        $intervention->update([
            'status' => 'cancelled',
        ]);

        return Redirect::route('interventions.index')->with('success', 'Demande annulée avec succès.');
    }

    public function validateIntervention(InterventionRequest $intervention)
    {
        $intervention->update([
            'is_validated' => true,
            'status' => 'completed', // Assuming validation means completion
            'completed_date' => now(),
        ]);

        return Redirect::route('interventions.index')->with('success', 'Demande validée avec succès.');
    }
}
