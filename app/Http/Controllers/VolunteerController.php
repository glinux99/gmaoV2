<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VolunteerController extends Controller
{
    /**
     * Affiche la liste paginée des bénévoles avec recherche.
     */
    public function index(Request $request)
    {
        $query = Volunteer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $volunteers = $query->orderBy('order')
            ->paginate(10)
            ->appends($request->only('search'));

        return Inertia::render('Admin/Volunteers', [
            'volunteers' => $volunteers,
            'filters'    => $request->only('search'),
        ]);
    }

    /**
     * Enregistre un nouveau bénévole.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'skills'      => 'nullable|json',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active'   => 'boolean',
            'order'       => 'nullable|integer|min:0',
        ]);

        // Upload de la photo
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('volunteers', 'public');
        }

        // Décodage des compétences (JSON → tableau)
        if (!empty($validated['skills'])) {
            $validated['skills'] = json_decode($validated['skills'], true);
        } else {
            $validated['skills'] = [];
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        if (empty($validated['order'])) {
            $validated['order'] = Volunteer::max('order') + 1;
        }

        Volunteer::create($validated);

        return redirect()->back()
            ->with('success', 'Bénévole créé avec succès.');
    }

    /**
     * Met à jour un bénévole existant.
     */
    public function update(Request $request, Volunteer $volunteer)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:50',
            'description'  => 'nullable|string',
            'skills'       => 'nullable|json',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active'    => 'boolean',
            'order'        => 'nullable|integer|min:0',
            'delete_photo' => 'boolean',
        ]);

        // Suppression de la photo si demandée
        if ($request->boolean('delete_photo') && $volunteer->photo) {
            Storage::disk('public')->delete($volunteer->photo);
            $volunteer->photo = null;
        }

        // Upload de la nouvelle photo
        if ($request->hasFile('photo')) {
            if ($volunteer->photo && !$request->boolean('delete_photo')) {
                Storage::disk('public')->delete($volunteer->photo);
            }
            $validated['photo'] = $request->file('photo')->store('volunteers', 'public');
        } else {
            unset($validated['photo']);
        }

        // Décodage des compétences
        if (isset($validated['skills'])) {
            $validated['skills'] = json_decode($validated['skills'], true);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['delete_photo']);

        $volunteer->update($validated);

        return redirect()->route('volunteers.index')
            ->with('success', 'Bénévole mis à jour.');
    }

    /**
     * Supprime un bénévole.
     */
    public function destroy(Volunteer $volunteer)
    {
        if ($volunteer->photo) {
            Storage::disk('public')->delete($volunteer->photo);
        }

        $volunteer->delete();

        return redirect()->route('volunteers.index')
            ->with('success', 'Bénévole supprimé.');
    }

    /**
     * Réordonne les bénévoles par glisser-déposer.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:volunteers,id',
        ]);

        foreach ($request->order as $index => $id) {
            Volunteer::where('id', $id)->update(['order' => $index]);
        }

        return redirect()->route('volunteers.index')
            ->with('success', 'Ordre mis à jour.');
    }
}
