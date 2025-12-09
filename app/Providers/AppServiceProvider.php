<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => function () {
                $user = Auth::user();
                if (!$user) {
                    return null;
                }

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        // URL de l'avatar via Spatie Media Library (collection 'avatar')
                        'avatar' => method_exists($user, 'getFirstMediaUrl') ? $user->getFirstMediaUrl('avatar') : null,
                        // Support d'un éventuel champ profile_photo_url
                        'profile_photo_url' => $user->profile_photo_url ?? null,
                        // Rôles (si Spatie Permission est utilisé)
                        'roles' => method_exists($user, 'roles') && $user->roles ? $user->roles->map(fn($r) => ['name' => $r->name])->values() : [],
                    ],
                ];
            },
        ]);
    }
}
