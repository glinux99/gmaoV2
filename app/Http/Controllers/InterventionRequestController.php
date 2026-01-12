<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\InterventionRequest;
use App\Models\Region;
use App\Models\Team;
use App\Models\Activity;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'statuses' => ['pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'Non validé'],
            'technicalComplexities' => ['Pas complexe', 'Peu complexe', 'Moyennement complexe', 'Très complexe'],
            'categories' => ['Réseau', 'Support Technique', 'Client', 'Support / Autres'],
            'priorities' => ['Faible', 'Moyenne', 'Élevée', 'Urgent'],
        ];
    }

    public function index(Request $request)
    {
        $query = InterventionRequest::query()
            ->with(['requestedByUser:id,name', 'requestedByConnection:id,customer_code,first_name,last_name', 'assignable', 'region:id,designation', 'zone:id,title']);

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
            'teams' => Team::all(['id', 'name']),
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
            'status' => 'nullable',
            'requested_by_user_id' => 'nullable|exists:users,id',
            'requested_by_connection_id' => 'nullable|exists:connections,id',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
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

        $intervention = InterventionRequest::create($validated);

        $activityTitle = $intervention->title;
        $activityRegionId = $intervention->region_id;
        $activityZoneId = $intervention->zone_id;

        if ($intervention->requested_by_connection_id) {
            $intervention->loadMissing('requestedByConnection');
            if ($intervention->requestedByConnection) {
                $activityTitle = 'Inter Cli ' . $intervention->requestedByConnection->customer_code . ' - ' . $intervention->title;
                $activityRegionId = $intervention->requestedByConnection->region_id;
                $activityZoneId = $intervention->requestedByConnection->zone_id;
            }
        }else{
             $activityTitle = 'Activité  Générée' .' - ' . $intervention->title;
        }

        // Si la demande est validée et assignée, créer une activité
        if ($validated['is_validated']) {
            Activity::updateOrCreate(
                ['intervention_request_id' => $intervention->id], // Critère de recherche
                [
                    'title' => $activityTitle,
                    'region_id' => $activityRegionId,
                    'zone_id' => $activityZoneId,
                    'user_id' => $intervention->assignable_type === User::class ? $intervention->assignable_id : null,
                    'assignable_type' => $intervention->assignable_type,
                    'assignable_id' => $intervention->assignable_id,
                    'status' => 'scheduled', // Ou 'in_progress' selon la logique métier
                    'priority' => $intervention->priority, // La priorité de l'activité dépend de la priorité de la demande d'intervention
                    'problem_resolution_description' => 'Activité générée suite à la création de la demande d\'intervention.',
                    'instructions' => $intervention->description, // Utiliser la description de la demande comme instruction initiale
                    'actual_start_time' => $intervention->scheduled_date ?? now(),
                    // Vous pouvez ajouter d'autres champs pertinents ici
                ]
            );

            return Redirect::route('interventions.index')->with('success', 'Demande créée et activité générée avec succès.');
        }

        return Redirect::route('interventions.index')->with('success', 'Demande créée.');
    }

    /**
     * Mise à jour d'un raccordement existant.
     */

   public function update(Request $request, InterventionRequest $intervention)
{

    // Note: J'ai changé le nom de la variable en $intervention pour plus de clarté
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'status' => 'nullable', // Validation plus stricte
        'description' => 'nullable|string',
        'requested_by_user_id' => 'nullable|exists:users,id',
        'requested_by_connection_id' => 'nullable|exists:connections,id',
        'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
        'assignable_id' => 'nullable|integer',
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

    $activityTitle = $intervention->title;
    $activityRegionId = $intervention->region_id;
    $activityZoneId = $intervention->zone_id;

      if ($intervention->requested_by_connection_id) {
            $intervention->loadMissing('requestedByConnection');
            if ($intervention->requestedByConnection) {
                $activityTitle = 'Inter Cli ' . $intervention->requestedByConnection->customer_code . ' - ' . $intervention->title;
                $activityRegionId = $intervention->requestedByConnection->region_id;
                $activityZoneId = $intervention->requestedByConnection->zone_id;
            }
        }else{
             $activityTitle = 'Activité  Générée' .' - ' . $intervention->title;
        }

    // Si l'intervention est validée et assignée, créer ou mettre à jour une activité
    if ($intervention->is_validated) {
        Activity::updateOrCreate(
            ['intervention_request_id' => $intervention->id], // Critère de recherche
            [
                'title' => $activityTitle,
                'region_id' => $activityRegionId,
                'zone_id' => $activityZoneId,
                'user_id' => $intervention->assignable_type === User::class ? $intervention->assignable_id : null,
                'assignable_type' => $intervention->assignable_type,
                'assignable_id' => $intervention->assignable_id,
                'status' => $intervention->status, // Ou 'in_progress' selon la logique métier
                'priority' => $intervention->priority, // La priorité de l'activité dépend de la priorité de la demande d'intervention
                'problem_resolution_description' => 'Activité générée suite à la validation de la demande d\'intervention.',
                'instructions' => $intervention->description, // Utiliser la description de la demande comme instruction initiale
                'actual_start_time' => $intervention->scheduled_date ?? now(),
                // Vous pouvez ajouter d'autres champs pertinents ici
            ]
        );

        return Redirect::route('interventions.index')->with('success', 'Mise à jour réussie et activité générée/mise à jour avec succès.');
    } elseif ($intervention->activity) {
        // Si l'intervention n'est plus validée ou assignée, mais qu'une activité existait, on peut la supprimer ou la marquer comme annulée
        $intervention->activity->update(['status' => 'cancelled']);
        return Redirect::route('interventions.index')->with('success', 'Mise à jour réussie et activité annulée.');
    }

    // IMPORTANT : Supprimez le "return $validated" qui bloquait Inertia
    return Redirect::route('interventions.index')->with('success', 'Mise à jour réussie.');
}

    public function assign(Request $request, InterventionRequest $intervention)
    {
        $validated = $request->validate([
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
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

        $activityTitle = $intervention->title;
        $activityRegionId = $intervention->region_id;
        $activityZoneId = $intervention->zone_id;

        if ($intervention->requested_by_connection_id) {
            $intervention->loadMissing('requestedByConnection');
            if ($intervention->requestedByConnection) {
                $activityTitle = 'Intervention pour client ' . $intervention->requestedByConnection->customer_code . ' - ' . $intervention->title;
                $activityRegionId = $intervention->requestedByConnection->region_id;
                $activityZoneId = $intervention->requestedByConnection->zone_id;
            }
        }

        // Si l'intervention est validée et assignée, créer une activité
        if ($intervention->status=='completed' && $intervention->assignable_id && $intervention->assignable_type) {
            Activity::updateOrCreate(
                ['intervention_request_id' => $intervention->id], // Critère de recherche
                [
                    'title' => $activityTitle,
                    'region_id' => $activityRegionId,
                    'zone_id' => $activityZoneId,
                    'user_id' => $intervention->assignable_type === User::class ? $intervention->assignable_id : null,
                    'assignable_type' => $intervention->assignable_type,
                    'assignable_id' => $intervention->assignable_id,
                    'status' => 'scheduled', // Ou 'in_progress' selon la logique métier
                    'priority' => $intervention->priority, // La priorité de l'activité dépend de la priorité de la demande d'intervention
                    'problem_resolution_description' => 'Activité générée suite à la validation de la demande d\'intervention.',
                    'instructions' => $intervention->description, // Utiliser la description de la demande comme instruction initiale
                    'actual_start_time' => $intervention->scheduled_date ?? now(),
                    // Vous pouvez ajouter d'autres champs pertinents ici
                ]
            );

            return Redirect::route('interventions.index')->with('success', 'Demande validée et activité générée avec succès.');
        }

        return Redirect::route('interventions.index')->with('success', 'Demande validée avec succès.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $data = array_map('str_getcsv', file($file->getRealPath()));

            // Supposer que la première ligne est l'en-tête
            $header = array_shift($data);

            foreach ($data as $row) {
                if (count($header) !== count($row)) {
                    Log::warning('Ligne ignorée : le nombre de colonnes ne correspond pas à l\'en-tête.', ['row' => $row]);
                    continue;
                }
                $interventionData = array_combine($header, $row);

                // Créer la demande d'intervention avec les données mappées
                InterventionRequest::create([
                    'title' => $interventionData['title'] ?? 'Titre non défini',
                    'description' => $interventionData['description'] ?? null,
                    'status' => $interventionData['status'] ?? 'pending',
                    'priority' => $interventionData['priority'] ?? 'Moyenne',
                    // Ajoutez d'autres champs ici en fonction des colonnes de votre CSV
                ]);
            }

            return Redirect::route('interventions.index')->with('success', 'Demandes d\'intervention importées avec succès.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Une erreur est survenue lors de l\'importation : ' . $e->getMessage());
        }
    }
}
