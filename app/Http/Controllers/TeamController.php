<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Affiche la page Organisation (Teams/Index)
     * Charge les équipes (avec leur parent) et les utilisateurs.
     */
    public function index()
    {
        // Récupération des équipes triées par 'order', avec le parent et le comptage des membres
        $teams = Team::with('parent')
            ->ordered()                       // Utilise le scope qui trie par 'order'
            ->withCount('users as members_count')
            ->get();

        // Récupération des utilisateurs avec leur équipe associée
        $users = User::with('team:id,name,color')
            ->orderBy('name')
            ->get();

        return Inertia::render('Teams', [
            'teams' => $teams,
            'users' => $users
        ]);
    }

    /**
     * Enregistre une nouvelle équipe.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'color'         => 'required|string|max:50',
            'max_capacity'  => 'nullable|integer|min:1',
            'location'      => 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'parent_id'     => 'nullable|exists:teams,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Team::create($validated);

        return back()->with('success', 'Équipe créée avec succès.');
    }

    /**
     * Met à jour une équipe existante.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'color'         => 'required|string|max:50',
            'max_capacity'  => 'nullable|integer|min:1',
            'location'      => 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'parent_id'     => 'nullable|exists:teams,id|not_in:' . $team->id, // Empêche de se prendre soi‑même comme parent
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $team->update($validated);

        return back()->with('success', 'Équipe mise à jour avec succès.');
    }

    /**
     * Supprime une équipe.
     * Les enfants sont reassignés à NULL (ou au parent de l'équipe supprimée selon besoin).
     */
    public function destroy(Team $team)
    {
        // Optionnel : réassigner les utilisateurs liés à NULL
        User::where('team_id', $team->id)->update(['team_id' => null]);

        // Réassigner les sous-équipes (enfants) à NULL
        Team::where('parent_id', $team->id)->update(['parent_id' => null]);

        $team->delete();

        return back()->with('success', 'Équipe supprimée avec succès.');
    }

    /**
     * Met à jour l'ordre d'affichage des équipes (drag & drop).
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|exists:teams,id',
        ]);

        foreach ($request->order as $index => $teamId) {
            Team::where('id', $teamId)->update(['order' => $index + 1]);
        }

        return back()->with('success', 'Ordre mis à jour.');
    }
}
