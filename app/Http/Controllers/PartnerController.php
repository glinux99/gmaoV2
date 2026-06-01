<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Partner;

class PartnerController extends Controller
{
    /**
     * Affiche la liste des partenaires (ordonnés).
     */
    public function index()
    {
        $partners = Partner::orderBy('order')->get();
        return Inertia::render('Admin/Partners', [
            'partners' => $partners
        ]);
    }

    /**
     * Enregistre un nouveau partenaire (avec upload logo).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'logo'        => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'website'     => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $partnerData = [
            'name'        => $validated['name'],
            'website'     => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
            'order'       => Partner::max('order') + 1,
        ];

        // Gestion du logo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('partners', 'media');
            $partnerData['logo'] = $path;
        }

        Partner::create($partnerData);

        return redirect()->back()->with('success', 'Partenaire ajouté avec succès.');
    }

    /**
     * Met à jour un partenaire existant (avec upload logo).
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'logo'        => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'website'     => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
            'delete_logo' => 'nullable|boolean',
        ]);

        $partner->name        = $validated['name'];
        $partner->website     = $validated['website'] ?? null;
        $partner->description = $validated['description'] ?? null;
        $partner->is_active   = $validated['is_active'] ?? true;

        // Suppression demandée de l'ancien logo
        if (!empty($validated['delete_logo']) && $partner->logo) {
            Storage::disk('media')->delete($partner->logo);
            $partner->logo = null;
        }

        // Nouvel upload (remplace l'ancien)
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien fichier
            if ($partner->logo) {
                Storage::disk('media')->delete($partner->logo);
            }
            $path = $request->file('logo')->store('partners', 'media');
            $partner->logo = $path;
        }

        $partner->save();

        return redirect()->back()->with('success', 'Partenaire mis à jour avec succès.');
    }

    /**
     * Supprime un partenaire (et son logo associé).
     */
    public function destroy(Partner $partner)
    {
        if ($partner->logo) {
            Storage::disk('media')->delete($partner->logo);
        }
        $partner->delete();

        return redirect()->back()->with('success', 'Partenaire supprimé avec succès.');
    }

    /**
     * Réordonne les partenaires (drag & drop).
     * Retourne une redirection Inertia (pas de JSON) pour rester cohérent.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:partners,id',
        ]);

        foreach ($request->order as $index => $id) {
            Partner::where('id', $id)->update(['order' => $index + 1]);
        }

        // Redirection avec message flash
        return redirect()->back()->with('success', 'Ordre des partenaires mis à jour.');
    }
}
