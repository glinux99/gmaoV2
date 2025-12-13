<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = request();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Team::query()->with(['members','teamLeader'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (request()->has('search')) {
            $search = request('search');
            $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('teamLeader', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('members', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }
        return Inertia::render('Teams/Teams', [
            'teams' => $query->get(),
            'filters' => $request->only(['search', 'start_date', 'end_date']),
            'technicians' => User::role('technician')->get(), // Pass all users for dropdowns
            'filters' => request()->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Teams/Teams', [
            'allUsers' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(Request $request)
    {
        // 1. Validation des données
        $validated = $request->validate([
           'name' => 'required|string|max:255',
           // S'assurer que le leader est un ID utilisateur valide ou null
           'team_leader_id' => 'nullable|exists:users,id',
           // Le champ 'members' est un tableau d'IDs
           'members' => 'array',
           // Chaque élément du tableau 'members' doit exister dans la table 'users'
           'members.*' => 'exists:users,id',
        ]);

        // 2. Définir le leader si non fourni
        if (empty($validated['team_leader_id'])) {
            $validated['team_leader_id'] = Auth::id();
        }

        // 3. Création de la nouvelle équipe
        $team = Team::create([
            'name' => $validated['name'],
            'team_leader_id' => $validated['team_leader_id'],
        ]);

        // 4. Attacher les membres (Correction majeure ici)
        if (!empty($validated['members'])) {


            $team->members()->attach($validated['members']);
        }

        // 5. Redirection
        return redirect()->route('teams.index')->with('success', 'Équipe créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function edit(Team $team)
    {
        return Inertia::render('Teams/Teams', [
            'team' => $team->load(['teamLeader', 'members']),
            'allUsers' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'team_leader_id' => 'nullable|exists:users,id',
            'members' => 'array',
            'members.*' => 'exists:users,id',
        ]);

        // If team_leader_id is not provided, set the current authenticated user as the team leader
        if (empty($validated['team_leader_id'])) {
            $validated['team_leader_id'] = Auth::id();
        }

        $team->update([
            'name' => $validated['name'],
            'team_leader_id' => $validated['team_leader_id'],
        ]);

        if (isset($validated['members'])) {
            $team->members()->sync($validated['members']);
        } else {
            $team->members()->detach(); // Remove all members if none are provided
        }

        return redirect()->route('teams.index')->with('success', 'Équipe mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->members()->detach(); // Detach all members before deleting the team
        $team->delete();
        return redirect()->route('teams.index')->with('success', 'Équipe supprimée avec succès.');
    }
}
