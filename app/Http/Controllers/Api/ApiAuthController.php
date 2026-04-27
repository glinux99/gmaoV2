<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    public function socialLogin(Request $request)
    {

        // 1. Validation de la requête
        $request->validate([
            'provider' => 'required|string',
            'provider_token' => 'required|string',
        ]);

        $token = $request->provider_token;
        $provider = $request->provider;

        try {
            // 2. Vérification du Token auprès de Google
            // On utilise l'endpoint de vérification de Google
            $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$token}");

            if ($response->failed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token Google invalide ou expiré.'
                ], 401);
            }

            $googleData = $response->json();
            $email = $googleData['email'];

            // 3. Récupérer ou Créer l'utilisateur
            // On se base sur l'email qui est unique
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleData['name'] ?? 'Google User',
                    'email' => $email,
                    'password' => bcrypt(Str::random(16)), // Mot de passe aléatoire car inutilisé
                    'profile_photo_url' => $googleData['picture'] ?? null,
                    'email_verified_at' => now(),
                    // Ajoute ici tes champs spécifiques si nécessaire (ex: provider_id)
                ]);
            }

            // 4. Générer le Token Sanctum
            $device = $request->header('User-Agent') ?? 'mobile_app';
            $sanctumToken = $user->createToken($device)->plainTextToken;

            // 5. Réponse formatée pour ton application Flutter
            return response()->json([
                'status' => true,
                'message' => 'Connexion réussie',
                'token' => $sanctumToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur Login Google: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur serveur lors de la connexion sociale.'
            ], 500);
        }
    }
}
