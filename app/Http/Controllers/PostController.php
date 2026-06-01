<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
     public function activities(Request $request)
    {
        $posts = Post::published()
            ->with(['author', 'category'])
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        $featured = Post::published()
            ->where('is_featured', true)
            ->with(['author', 'category'])
            ->latest('published_at')
            ->first();

        $categories = Category::has('posts')->get();

        return Inertia::render('Public/Activities', [
            'posts' => $posts,
            'featuredPost' => $featured,
            'categories' => $categories,
        ]);
    }

    public function index(Request $request): Response
    {
        $query = Post::with(['author', 'category', 'tags']);

        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id',
            'title',
            'slug',
            'status',
            'views',
            'likes',
            'published_at',
            'created_at',
            'updated_at',
        ];

        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('filters')) {
            $filters = $request->input('filters');

            if (!empty($filters['global']['value'])) {
                $globalFilter = $filters['global']['value'];

                $query->where(function ($q) use ($globalFilter) {
                    $q->where('title', 'like', '%' . $globalFilter . '%')
                        ->orWhere('slug', 'like', '%' . $globalFilter . '%')
                        ->orWhere('content', 'like', '%' . $globalFilter . '%')
                        ->orWhere('excerpt', 'like', '%' . $globalFilter . '%')
                        ->orWhere('status', 'like', '%' . $globalFilter . '%')
                        ->orWhere('seo_title', 'like', '%' . $globalFilter . '%')
                        ->orWhere('seo_description', 'like', '%' . $globalFilter . '%')
                        ->orWhereHas('author', function ($subQ) use ($globalFilter) {
                            $subQ->where('name', 'like', '%' . $globalFilter . '%');
                        })
                        ->orWhereHas('category', function ($subQ) use ($globalFilter) {
                            $subQ->where('name', 'like', '%' . $globalFilter . '%');
                        })
                        ->orWhereHas('tags', function ($subQ) use ($globalFilter) {
                            $subQ->where('name', 'like', '%' . $globalFilter . '%');
                        });
                });
            }

            if (!empty($filters['status']['value'])) {
                $query->where('status', $filters['status']['value']);
            }

            if (!empty($filters['category.name']['value'])) {
                $query->whereHas('category', function ($subQ) use ($filters) {
                    $subQ->where('name', $filters['category.name']['value']);
                });
            }

            if (!empty($filters['author']['value'])) {
                $authorFilter = $filters['author']['value'];

                $query->whereHas('author', function ($subQ) use ($authorFilter) {
                    $subQ->where('name', 'like', '%' . $authorFilter . '%');
                });
            }

            if (!empty($filters['title']['value'])) {
                $query->where('title', 'like', '%' . $filters['title']['value'] . '%');
            }

            if (!empty($filters['published_at']['value'])) {
                $query->whereDate('published_at', $filters['published_at']['value']);
            }

            if (!empty($filters['is_featured']['value'])) {
                $query->where('is_featured', filter_var($filters['is_featured']['value'], FILTER_VALIDATE_BOOLEAN));
            }
        }

        $posts = $query->paginate($request->input('rows', 10))->withQueryString();

        return Inertia::render('Posts', [
            'posts' => $posts,
            'categories' => Category::select('id', 'name', 'slug', 'color')->get(),
            'tags' => Tag::select('id', 'name', 'slug')->get(),
            'users' => User::select('id', 'name')->get(),
            'queryParams' => $request->all(),
        ]);
    }
    public function duplicate(Request $request)
{
    // 1. Trouver le post original (on suppose que l'id est envoyé dans la requête)
    $originalPost = Post::findOrFail($request->id);

    // 2. Répliquer l'objet (crée une copie sans ID)
    $newPost = $originalPost->replicate();

    // 3. Personnaliser les données
    $newPost->title = $originalPost->title . ' (Copie)';
    $newPost->slug = Str::slug($newPost->title) . '-' . Str::random(5);
    $newPost->status = 'draft';
    $newPost->views = 0;
    $newPost->likes = 0;

    $newPost->save();

    // 4. Dupliquer les relations (Many-to-Many)
    $newPost->tags()->sync($originalPost->tags->pluck('id'));

    return redirect()->route('posts.index')
        ->with('success', 'Publication dupliquée avec succès.');
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'views' => ['nullable', 'integer'],
            'likes' => ['nullable', 'integer'],
            'author_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['views'] = $validated['views'] ?? 0;
        $validated['likes'] = $validated['likes'] ?? 0;
        $validated['is_featured'] = $request->boolean('is_featured');

        $post = Post::create(collect($validated)->except('cover_image')->toArray());

        if ($request->hasFile('cover_image')) {
            $media = $post->addMediaFromRequest('cover_image')
                ->toMediaCollection('cover');

            $post->update(['cover_image' => $media->getUrl()]);
        }

        if (!empty($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return redirect()->route('posts.index')->with('success', 'Publication créée avec succès.');
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug,' . $post->id],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'views' => ['nullable', 'integer'],
            'likes' => ['nullable', 'integer'],
            'author_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_featured'] = $request->boolean('is_featured');

        $post->update(collect($validated)->except('cover_image')->toArray());

        if ($request->hasFile('cover_image')) {
            $media = $post->addMediaFromRequest('cover_image')
                ->toMediaCollection('cover');

            $post->update(['cover_image' => $media->getUrl()]);
        }

        if (array_key_exists('tags', $validated)) {
            $post->tags()->sync($validated['tags'] ?? []);
        }

        return redirect()->route('posts.index')->with('success', 'Publication mise à jour avec succès.');
    }

    public function destroy(Post $post)
    {
        $post->tags()->detach();
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Publication supprimée avec succès.');
    }
}
