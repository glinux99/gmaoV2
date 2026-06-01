<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TestimonialController extends Controller
{
    /**
     * Affiche la liste paginée des témoignages avec recherche.
     */
    public function index(Request $request)
    {
        $query = Testimonial::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $testimonials = $query->orderBy('order')
            ->paginate(10)
            ->appends($request->only('search'));

        return Inertia::render('Admin/Testimonials', [
            'testimonials' => $testimonials,
            'filters'      => $request->only('search'),
        ]);
    }

    /**
     * Enregistre un nouveau témoignage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'position'  => 'nullable|string|max:255',
            'company'   => 'nullable|string|max:255',
            'content'   => 'required|string',
            'rating'    => 'required|integer|min:1|max:5',
            'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active' => 'boolean',
            'order'     => 'nullable|integer|min:0',
        ]);

        // Upload de l'avatar
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('testimonials', 'media');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        if (empty($validated['order'])) {
            $validated['order'] = Testimonial::max('order') + 1;
        }

        Testimonial::create($validated);

        return redirect()->route('testimonials.index')
            ->with('success', 'Témoignage créé avec succès.');
    }

    /**
     * Met à jour un témoignage existant.
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'position'      => 'nullable|string|max:255',
            'company'       => 'nullable|string|max:255',
            'content'       => 'required|string',
            'rating'        => 'required|integer|min:1|max:5',
            'avatar'        => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active'     => 'boolean',
            'order'         => 'nullable|integer|min:0',
            'delete_avatar' => 'boolean',
        ]);

        // Suppression de l'avatar si demandé
        if ($request->boolean('delete_avatar') && $testimonial->avatar) {
            Storage::disk('media')->delete($testimonial->avatar);
            $testimonial->avatar = null;
        }

        // Upload du nouvel avatar
        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar && !$request->boolean('delete_avatar')) {
                Storage::disk('media')->delete($testimonial->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('testimonials', 'media');
        } else {
            unset($validated['avatar']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['delete_avatar']);

        $testimonial->update($validated);

        return redirect()->route('testimonials.index')
            ->with('success', 'Témoignage mis à jour.');
    }

    /**
     * Supprime un témoignage.
     */
    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->avatar) {
            Storage::disk('media')->delete($testimonial->avatar);
        }

        $testimonial->delete();

        return redirect()->route('testimonials.index')
            ->with('success', 'Témoignage supprimé.');
    }

    /**
     * Réordonne les témoignages par glisser-déposer.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:testimonials,id',
        ]);

        foreach ($request->order as $index => $id) {
            Testimonial::where('id', $id)->update(['order' => $index]);
        }

        return redirect()->route('testimonials.index')
            ->with('success', 'Ordre mis à jour.');
    }
}
