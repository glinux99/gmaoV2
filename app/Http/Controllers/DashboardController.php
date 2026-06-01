<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Message;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistiques principales
        $visitors = 28450; // À remplacer par une vraie métrique (ex: Google Analytics API ou table site_visits)
        $posts = Post::count();
        $documents = Document::count();
        $projectsInProgress = Project::where('is_active', 'in_progress')->count();
        $members = User::count();
        $unreadMessages = Message::where('is_read', false)->count();

        // Tendances (exemple simple : comparaison avec le mois précédent)
        $lastMonthPosts = Post::where('created_at', '<', now()->startOfMonth())->count();
        $postTrend = $lastMonthPosts > 0 ? round(($posts - $lastMonthPosts) / $lastMonthPosts * 100, 1) : 0;

        $lastMonthDocs = Document::where('created_at', '<', now()->startOfMonth())->count();
        $documentTrend = $lastMonthDocs > 0 ? round(($documents - $lastMonthDocs) / $lastMonthDocs * 100, 1) : 0;

        $lastMonthProjects = Project::where('is_active', 'in_progress')
            ->where('created_at', '<', now()->startOfMonth())
            ->count();
        $projectTrend = $lastMonthProjects > 0 ? round(($projectsInProgress - $lastMonthProjects) / $lastMonthProjects * 100, 1) : 0;

        // 2. Projets récents
        $recentProjects = Project::with('team') // suppose une relation "team" qui renvoie une collection d'utilisateurs
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id'       => $project->id,
                    'name'     => $project->title,
                    'status'   => $this->translateStatus($project->status),
                    'progress' => $project->progress ?? 0,
                    'team'     => $project->team->pluck('initials')->toArray(), // chaque User a un champ 'initials'
                    'budget'   => $project->budget ?? 'N/A',
                    'date'     => $project->created_at->format('d M Y'),
                ];
            });

        // 3. Messages récents (non lus ou les plus récents)
        $recentMessages = Message::with('sender')
            ->where('is_read', false)
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($msg) {
                return [
                    'id'      => $msg->id,
                    'sender'  => $msg->sender->name ?? 'Inconnu',
                    'subject' => $msg->subject,
                    'time'    => $msg->created_at->diffForHumans(),
                    'unread'  => !$msg->is_read,
                    'avatar'  => $msg->sender->avatar_url ?? null,
                ];
            });

        // 4. Événements de la timeline (exemple basé sur une table activity_log ou construit manuellement)
        $timelineEvents = $this->getRecentActivities();

        // 5. Stockage (exemple)
        $storage = [
            'used'       => 64, // GB (à calculer dynamiquement)
            'total'      => 100,
            'documents'  => 45.2,
            'media'      => 18.8,
            'usedPercent' => 64,
        ];

        return Inertia::render('Dashboard', [
            'stats'          => [
                'visitors'      => $visitors,
                'visitorTrend'  => 12.5, // À calculer dynamiquement
                'posts'         => $posts,
                'postTrend'     => $postTrend,
                'documents'     => $documents,
                'documentTrend' => $documentTrend,
                'projects'      => $projectsInProgress,
                'projectTrend'  => $projectTrend,
                'members'       => $members,
                'messages'      => $unreadMessages,
            ],
            'recentProjects'  => $recentProjects,
            'recentMessages'  => $recentMessages,
            'timelineEvents'  => $timelineEvents,
            'storage'         => $storage,
        ]);
    }

    /**
     * Traduit le statut technique en libellé lisible.
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            'in_progress' => 'En cours',
            'completed'   => 'Terminé',
            'planned'     => 'Planifié',
            'paused'      => 'En pause',
            default       => $status,
        };
    }

    /**
     * Récupère les activités récentes pour la timeline.
     * Idéalement depuis une table dédiée (ex: spatie/laravel-activitylog).
     * Ici, on génère un exemple semi-dynamique.
     */
    private function getRecentActivities(): array
    {
        // Exemple : remplacer par vos propres données
        return [
            [
                'status' => 'Document uploadé',
                'date'   => now()->subHours(2)->format('d M H:i'),
                'icon'   => 'pi pi-file-pdf',
                'color'  => '#10b981',
                'desc'   => 'Rapport_Annuel_2024.pdf'
            ],
            [
                'status' => 'Nouvelle publication',
                'date'   => now()->subDay()->format('d M H:i'),
                'icon'   => 'pi pi-megaphone',
                'color'  => '#6366f1',
                'desc'   => 'Lancement du nouveau projet agricole.'
            ],
            [
                'status' => 'Nouveau membre',
                'date'   => now()->subDays(3)->format('d M H:i'),
                'icon'   => 'pi pi-user-plus',
                'color'  => '#f59e0b',
                'desc'   => 'Dr. Mukwege a rejoint l\'équipe Santé.'
            ],
            [
                'status' => 'Projet clôturé',
                'date'   => now()->subDays(5)->format('d M H:i'),
                'icon'   => 'pi pi-check-circle',
                'color'  => '#3b82f6',
                'desc'   => 'Le projet École Primaire est terminé à 100%.'
            ],
        ];
    }
}
