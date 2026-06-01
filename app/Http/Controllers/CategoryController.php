<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
     public function index()
    {
        return Inertia::render('Categorie', [
            // On charge toutes les catégories avec le nombre d'articles associés
            'categories' => Category::withCount('posts')
                ->orderBy('name')
                ->get(),

            // On charge tous les tags avec le nombre d'articles associés
            'tags' => Tag::withCount('posts')
                ->orderBy('name')
                ->get(),
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create($validated);

        return back()->with('success', 'Catégorie créée avec succès.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // On ignore l'ID de la catégorie actuelle pour la validation unique du slug
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Category $category)
    {
        // Optionnel : Vous pouvez empêcher la suppression si la catégorie contient des articles
        // if ($category->posts()->count() > 0) {
        //     return back()->withErrors(['error' => 'Impossible de supprimer une catégorie contenant des articles.']);
        // }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée avec succès.');
    }
}
