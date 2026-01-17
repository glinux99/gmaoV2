<?php

namespace App\Http\Controllers;

use App\Imports\InterventionRequestImport;
use App\Imports\InterventionRequestImport as ImportsInterventionRequestImport;
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
use Maatwebsite\Excel\Facades\Excel;

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
            ->with(['requestedByUser:id,name', 'requestedByConnection:id,customer_code,first_name,last_name', 'assignable', 'region:id,designation', 'zone:id,nomenclature']);

        // --- FILTRES & RECHERCHE (Lazy Loading) ---
        $filters = $request->input('filters', []);
        if (isset($filters['global']['value'])) {
            $search = $filters['global']['value'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('intervention_reason', 'like', "%{$search}%")
                  ->orWhereHas('requestedByConnection', fn($sq) =>
                        $sq->where('customer_code', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhere('first_name', 'like', "%{$search}%")
                    );
            });
        }

        // Filtres par colonne
        if (isset($filters['status']['value'])) {
            $query->where('status', $filters['status']['value']);
        }
        if (isset($filters['priority']['value'])) {
            $query->where('priority', $filters['priority']['value']);
        }

        // --- TRI (Lazy Loading) ---
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        if ($sortField) {
            // Gérer le tri sur les relations
            if (str_contains($sortField, '.')) {
                // Exemple pour trier par nom de région (à adapter si besoin)
                // [$relation, $field] = explode('.', $sortField);
                // $query->join('regions', 'intervention_requests.region_id', '=', 'regions.id')->orderBy("regions.{$field}", $sortOrder);
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return inertia('Tasks/Interventions', array_merge([
            'interventionRequests' => $query->paginate($request->input('per_page', 15)),
            'filters' => $request->all(['search', 'status', 'region_id', 'zone_id']),
            'users' => User::all(['id', 'name']),
            'teams' => Team::all(['id', 'name']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'nomenclature']),
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
        'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
    ]);

    try {
        // Utilisation d'une transaction pour éviter les imports partiels corrompus
        DB::beginTransaction();

        Excel::import(new ImportsInterventionRequestImport, $request->file('file'));

        DB::commit();
        return redirect()->route('interventions.index')
            ->with('success', "L'importation a été effectuée avec succès.");

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        DB::rollBack(); // On annule tout si une ligne est invalide
        return $e;
        $failures = $e->failures();
        $errors = [];

        foreach ($failures as $failure) {
            $row = $failure->row();
            $attribute = $failure->attribute();
            $errorMessages = implode(', ', $failure->errors());
            // On récupère la valeur qui a posé problème
            $providedValue = $failure->values()[$attribute] ?? 'N/A';

            $errors[] = "Ligne {$row} (Champ '{$attribute}'): {$errorMessages} [Valeur: {$providedValue}]";
        }

        return back()->with('import_errors', $errors)
                     ->with('error', 'L\'importation a échoué à cause d\'erreurs de validation.');

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error("Erreur import interventions: " . $e->getMessage(), [
            'exception' => $e,
            'user_id' => auth()->id()
        ]);

        return back()->with('error', "Une erreur technique est survenue. Vérifiez le format de votre fichier.");
    }
}
}
