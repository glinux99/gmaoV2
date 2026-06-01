<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Initiative;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\Partner;
use App\Models\Testimonial;
use App\Models\FaqItem;
use App\Models\Province;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicController extends Controller
{
    public function home()
    {
        $heroSlides = HeroSlide::where('is_active', true)->orderBy('order')->get();
        $globalStats = [];
        $initiatives = Initiative::where('is_active', true)->orderBy('order')->get();
        $projects = Project::with('province')->where('is_active', true)->get();
        $teamMembers = TeamMember::where('is_active', true)->orderBy('order')->get();
        $partners = Partner::where('is_active', true)->orderBy('order')->get();
        $testimonials = Testimonial::where('is_active', true)->orderBy('order')->get();
        $latestPosts = Post::published()
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'cover_image', 'published_at']);
        $faqItems = FaqItem::where('is_active', true)->orderBy('order')->get();
        $provinces = Province::all()->map(fn($p) => [
            'label' => $p->name,
            'value' => $p->code,
        ]);
        $donationAmounts = [15, 30, 50, 100, 250];
        $settings = Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Public/Home', [
            'settings' => $settings,
            'heroSlides' => $heroSlides,
            'globalStats' => $globalStats,
            'initiatives' => $initiatives,
            'projects' => $projects,
            'teamMembers' => $teamMembers,
            'partners' => $partners,
            'testimonials' => $testimonials,
            'latestPosts' => $latestPosts,
            'faqItems' => $faqItems,
            'provinces' => $provinces,
            'donationAmounts' => $donationAmounts,
        ]);
    }

    // Affiche la liste des posts (page Activités)
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
        $settings = Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Public/Activities', [
            'posts' => $posts,
            'featuredPost' => $featured,
            'categories' => $categories,
            'settings' => $settings,
        ]);
    }

    // Affiche le détail d'un article (slug)
    public function activityDetails($slug)
    {

        try {
            $post = Post::published()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                      ->orWhere('id', $slug);
            })
            ->with(['author', 'category', 'tags'])
            ->firstOrFail();

        // Incrémenter le compteur de vues (seulement si l'utilisateur n'est pas l'auteur, optionnel)
        $post->increment('views');

        // Récupérer les commentaires approuvés (avec pagination ou non)
        $comments = $post->comments()
            ->where('is_approved', true)
            ->with('user')
            ->latest()
            ->paginate(20);

        // Articles similaires (même catégorie)
        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get(['id', 'title', 'slug', 'cover_image', 'published_at']);

        $settings = Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Public/ActivitiesDetails', [
            'post' => $post,
            'comments' => $comments,
            'relatedPosts' => $relatedPosts,
            'settings' => $settings,
        ]);
        } catch (\Exception $th) {
            //throw $th;
            return $th;
        }
    }

    // API pour ajouter un commentaire (via axios)
    public function storeComment(Request $request, Post $post)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'content' => 'required|string|max:2000',
        ]);

        $comment = $post->comments()->create([
            'user_id' => Auth::id(), // si l'utilisateur est connecté, sinon null
            'author_name' => $validated['name'],
            'author_email' => $validated['email'],
            'content' => $validated['content'],
            'is_approved' => true, // ou false selon modération
        ]);

        return response()->json(['message' => 'Commentaire ajouté', 'comment' => $comment]);
    }

    // API pour liker/unliker un post
    public function toggleLike(Post $post)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Authentification requise'], 401);
    }

    $liked = $user->likedPosts()->toggle($post->id);

    // Mise à jour du compteur dénormalisé
    if (count($liked['attached']) > 0) {
        $post->increment('likes');
    } else {
        $post->decrement('likes');
    }

    return response()->json([
        'liked' => count($liked['attached']) > 0,
        'likes_count' => $post->fresh()->likes  // ou $post->likes après incrément
    ]);
}

    public function contact()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return Inertia::render('Public/Contacts', [
            'settings' => $settings,
        ]);
    }
    public function about(){
        // Dans votre contrôleur

    $teams = Team::with('parent', 'users') // users chargés
    ->ordered()
    ->get();
   $testimonials =Testimonial::where('is_active', true)->orderBy('order')->get();
  $partners = Partner::where('is_active', true)->orderBy('order')->get();
    return Inertia::render('Public/About', [
        'teams' => $teams,
        'testimonials' => $testimonials,
        'partners' => $partners,

        // ... autres props
    ]);
    }
}
