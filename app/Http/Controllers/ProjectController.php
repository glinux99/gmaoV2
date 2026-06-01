<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Affiche la liste paginée des projets avec recherche.
     */
    public function index(Request $request)
    {
        $query = Project::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        $categories = Category::orderBy('name')->get();
        $projects = $query->orderBy('order')
            ->paginate(10)
            ->appends($request->only('search'));

        return Inertia::render('Admin/Projects', [
            'projects' => $projects,
             'categories' => $categories,
            'filters'  => $request->only('search'),
        ]);
    }

    /**
     * Enregistre un nouveau projet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'required|string|in:in_progress,completed,paused,cancelled',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
            'is_active'   => 'boolean',
            'order'       => 'nullable|integer|min:0',
        ]);

        // Upload de l'image
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('projects', 'media');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        // Ordre par défaut si non fourni
        if (empty($validated['order'])) {
            $validated['order'] = Project::max('order') + 1;
        }

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Met à jour un projet existant.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'required|string|in:in_progress,completed,paused,cancelled',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
            'is_active'   => 'boolean',
            'order'       => 'nullable|integer|min:0',
        ]);

        // Suppression de l'ancienne image si demandée
        if ($request->boolean('delete_image') && $project->image) {
            Storage::disk('media')->delete($project->image);
            $project->image = null;
        }

        // Upload de la nouvelle image
        if ($request->hasFile('image')) {
            if ($project->image && !$request->boolean('delete_image')) {
                Storage::disk('media')->delete($project->image);
            }
            $validated['image'] = $request->file('image')->store('projects', 'media');
        } else {
            unset($validated['image']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        // Ne pas envoyer delete_image à la base
        unset($validated['delete_image']);

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Projet mis à jour.');
    }

    /**
     * Supprime un projet.
     */
    public function destroy(Project $project)
    {
        if ($project->image) {
            Storage::disk('media')->delete($project->image);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Projet supprimé.');
    }

    /**
     * Réordonne les projets par glisser-déposer.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:projects,id',
        ]);

        foreach ($request->order as $index => $id) {
            Project::where('id', $id)->update(['order' => $index]);
        }

        return redirect()->route('projects.index')
            ->with('success', 'Ordre mis à jour.');
    }
}
