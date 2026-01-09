<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionInDatabase
{
    /**
     * Gère une requête entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // On ne vérifie que si l'utilisateur est authentifié
        if (Auth::check()) {
            $sessionId = $request->session()->getId();
            $userId = Auth::id();
            $ipAddress = $request->ip();
            $userAgent = substr($request->header('User-Agent'), 0, 500);

            // On cherche la session dans la base de données
            $sessionExists = DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $userId)
                ->where('ip_address', $ipAddress)
                ->where('user_agent', $userAgent)
                ->exists();

            // Si la session n'existe pas, cela signifie qu'elle a été invalidée à distance.
            if (!$sessionExists) {
                // On déconnecte l'utilisateur de force
                Auth::logout();

                // On invalide la session locale et on régénère le token CSRF
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // On redirige vers la page de connexion avec un message d'erreur
                return redirect()->route('login')->with('error', 'Votre session a été déconnectée depuis un autre appareil.');
            }
        }

        return $next($request);
    }
}
