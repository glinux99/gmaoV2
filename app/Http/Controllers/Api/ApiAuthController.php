<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{



   public function login(Request $request)
    {
        // 1. Validation croisée
        // Exige SOIT (login + password) SOIT (provider + provider_token)
        $request->validate([
            'login'          => 'required_without:provider|string',
            'password'       => 'required_with:login|string',
            'provider'       => 'required_without:login|string',
            'provider_token' => 'required_with:provider|string',
        ]);

        try {
            $user = null;

            // ==========================================
            // SCÉNARIO A : CONNEXION SOCIALE (GOOGLE)
            // ==========================================
            if ($request->has('provider') && $request->provider === 'google') {

                $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$request->provider_token}");

                if ($response->failed()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Token Google invalide ou expiré.'
                    ], 401);
                }

                $googleData = $response->json();
                $email = $googleData['email'];

                // Récupérer ou Créer l'utilisateur
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'              => $googleData['name'] ?? 'Google User',
                        'password'          => bcrypt(Str::random(16)), // Mot de passe aléatoire sécurisé
                        'profile_photo_url' => $googleData['picture'] ?? null,
                        'email_verified_at' => now(),
                    ]
                );

            }
            // ==========================================
            // SCÉNARIO B : CONNEXION CLASSIQUE
            // ==========================================
            elseif ($request->has('login')) {

                // Vérifie si le 'login' est un email ou un nom d'utilisateur
                $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

                $credentials = [
                    $fieldType => $request->login,
                    'password' => $request->password,
                ];

                if (!Auth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Identifiants incorrects. Veuillez vérifier vos informations.'
                    ], 401);
                }

                $user = Auth::user();
            }
            // ==========================================
            // SÉCURITÉ : FOURNISSEUR NON RECONNU
            // ==========================================
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'Méthode de connexion non supportée.'
                ], 400);
            }

            // ==========================================
            // GÉNÉRATION DU TOKEN & RÉPONSE COMMUNE
            // ==========================================
            $device = $request->header('User-Agent') ?? 'mobile_app';

            // Création du Token Sanctum
            $sanctumToken = $user->createToken($device)->plainTextToken;

            // Réponse formatée unique pour ton application (Flutter, etc.)
            return response()->json([
                'status'  => true,
                'message' => 'Connexion réussie',
                'token'   => $sanctumToken,
                'user'    => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'created_at'        => $user->created_at,
                    'updated_at'        => $user->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur Login (Unifié): " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Erreur serveur lors de la connexion.'
            ], 500);
        }
    }
}
