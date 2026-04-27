<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExcludeRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
     public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Vérifie si l'utilisateur n'a AUCUN rôle (ou le rôle explicite 'visitor')
        // Adapte cette condition à ta logique de rôles (Spatie, colonne 'role' en base, etc.)
        $isVisitor = $user->roles()->count() === 0 || $user->hasRole('visitor');

        // Si c'est un visiteur ET qu'il n'est pas DÉJÀ sur la page d'attente, on le redirige.
        if ($isVisitor && !$request->routeIs('socialite.visitor')) {
            return redirect()->route('socialite.visitor');
        }

        // S'il n'est PAS visiteur (il a été validé), mais qu'il essaie d'aller sur la page d'attente, on le renvoie au Dashboard
        if (!$isVisitor && $request->routeIs('socialite.visitor')) {
            return redirect()->route('dashboard.index'); // Redirige vers le vrai dashboard
        }

        return $next($request);
    }
}
