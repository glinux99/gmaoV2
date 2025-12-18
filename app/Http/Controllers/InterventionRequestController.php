<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\InterventionRequest;
use App\Models\Region;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;

class InterventionRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InterventionRequest::query()->with(['requestedByUser', 'requestedByConnection', 'assignedToUser', 'region', 'zone']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('requestedByUser', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('requestedByConnection', function ($sq) use ($search) {
                        $sq->where('customer_code', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignedToUser', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return inertia('Tasks/Interventions', [
            'interventionRequests' => $query->latest()->paginate($request->input('per_page', 10)),
            'filters' => $request->all(['search']),
            'users' => User::all(['id', 'name']),
            'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
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
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('tasks/Interventions', [
            'users' => User::all(['id', 'name']),
            'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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
            'min_time_hours' => 'nullable|integer|min:0',
            'max_time_hours' => 'nullable|integer|min:0',
            'comments' => 'nullable|string',
            'priority' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        InterventionRequest::create($validated);

        return redirect()->route('interventions.index')->with('success', 'Demande d\'intervention créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InterventionRequest $interventionRequest)
    {
        return inertia('InterventionRequests/Show', [
            'interventionRequest' => $interventionRequest->load(['requestedByUser', 'requestedByConnection', 'assignedToUser', 'region', 'zone']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InterventionRequest $interventionRequest)
    {
        return inertia('InterventionRequests/Edit', [
            'interventionRequest' => $interventionRequest->load(['requestedByUser', 'requestedByConnection', 'assignedToUser', 'region', 'zone']),
            'users' => User::all(['id', 'name']),
            'connections' => Connection::all(['id', 'customer_code', 'first_name', 'last_name']),
            'regions' => Region::all(['id', 'designation']),
            'zones' => Zone::all(['id', 'title']),
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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InterventionRequest $interventionRequest)
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
            'min_time_hours' => 'nullable|integer|min:0',
            'max_time_hours' => 'nullable|integer|min:0',
            'comments' => 'nullable|string',
            'priority' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        $interventionRequest->update($validated);

        return redirect()->route('interventions.index')->with('success', 'Demande d\'intervention mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InterventionRequest $interventionRequest)
    {
        $interventionRequest->delete();

        return redirect()->route('interventions.index')->with('success', 'Demande d\'intervention supprimée avec succès.');
    }
}
