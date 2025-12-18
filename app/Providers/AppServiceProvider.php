<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
    // Source - https://stackoverflow.com/a
// Posted by O. Jones
// Retrieved 2025-12-17, License - CC BY-SA 3.0

    function RADIANS($degrees)
    {
         return 0.0174532925 * $degrees;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    //      if (DB::connection() instanceof SQLiteConnection) {
    //     DB::connection()->getPdo()->sqliteCreateFunction('ACOS', function ($degrees) {
    //         return acos($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('COS', function ($degrees) {
    //         return cos($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('SIN', function ($degrees) {
    //         return sin($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('TAN', function ($degrees) {
    //         return tan($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('RADIANS', function ($degrees) {
    //         return $this->RADIANS($degrees);
    //     });

    //     DB::connection()->getPdo()->sqliteCreateFunction('SQRT', function ($number) {
    //         return sqrt($number);
    //     });


    // };
      Schema::defaultStringLength(125);
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
