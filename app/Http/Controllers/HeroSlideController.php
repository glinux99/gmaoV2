<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index()
    {
        $slides = HeroSlide::orderBy('order')->get();
        return Inertia::render('Admin/HeroSliders', [
            'slides' => $slides
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image'          => 'required|image|mimes:jpeg,png,gif,webp|max:2048',
            'badge'          => 'nullable|string|max:255',
            'title_pre'      => 'nullable|string|max:255',
            'title_highlight'=> 'nullable|string|max:255',
            'title_post'     => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'is_active'      => 'boolean',
        ]);

        // Stockage de l'image
        $path = $request->file('image')->store('hero-slides', 'public');

        HeroSlide::create([
            'image'           => $path,
            'badge'           => $validated['badge'],
            'title_pre'       => $validated['title_pre'],
            'title_highlight' => $validated['title_highlight'],
            'title_post'      => $validated['title_post'],
            'description'     => $validated['description'],
            'is_active'       => $validated['is_active'] ?? true,
            'order'           => HeroSlide::max('order') + 1,
        ]);

        return redirect()->back()->with('success', 'Slide ajouté.');
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        $validated = $request->validate([
            'image'          => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'badge'          => 'nullable|string|max:255',
            'title_pre'      => 'nullable|string|max:255',
            'title_highlight'=> 'nullable|string|max:255',
            'title_post'     => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'is_active'      => 'boolean',
            'delete_image'   => 'nullable|boolean',
        ]);

        // Suppression de l'image existante si demandée
        if (!empty($validated['delete_image']) && $heroSlide->image) {
            Storage::disk('public')->delete($heroSlide->image);
            $heroSlide->image = null;
        }

        // Nouvelle image
        if ($request->hasFile('image')) {
            if ($heroSlide->image) {
                Storage::disk('public')->delete($heroSlide->image);
            }
            $path = $request->file('image')->store('hero-slides', 'public');
            $heroSlide->image = $path;
        }

        $heroSlide->badge            = $validated['badge'];
        $heroSlide->title_pre        = $validated['title_pre'];
        $heroSlide->title_highlight  = $validated['title_highlight'];
        $heroSlide->title_post       = $validated['title_post'];
        $heroSlide->description      = $validated['description'];
        $heroSlide->is_active        = $validated['is_active'] ?? true;
        $heroSlide->save();

        return redirect()->back()->with('success', 'Slide mis à jour.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        if ($heroSlide->image) {
            Storage::disk('public')->delete($heroSlide->image);
        }
        $heroSlide->delete();
        return redirect()->back()->with('success', 'Slide supprimé.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:hero_slides,id',
        ]);

        foreach ($request->order as $index => $id) {
            HeroSlide::where('id', $id)->update(['order' => $index + 1]);
        }

        return redirect()->back()->with('success', 'Ordre mis à jour.');
    }
}
