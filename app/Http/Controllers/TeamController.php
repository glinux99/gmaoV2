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
        $query = Team::query()->with(['members','teamLeader']);

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

        $team = Team::create([
            'name' => $validated['name'],
            'team_leader_id' => $validated['team_leader_id'],
        ]);

        if (isset($validated['members'])) {
            $teamUser = TeamUser::where('team_id', $team->id)->first();
            $teamUser->members()->sync($validated['members']);
        }

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
