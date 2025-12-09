<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function callback(string $provider)
    {
        try {

            $socialUser = Socialite::driver($provider)->user();

            // Find user if they already exist
            $user = User::where('provider_name', $provider)
                        ->where('provider_id', $socialUser->getId())
                        ->first();

            if ($user) {
                // Mettre à jour l'avatar si disponible depuis le provider
                if (method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                    try {
                        $user->clearMediaCollection('avatar');
                        $user->addMediaFromUrl($socialUser->getAvatar())
                             ->usingFileName('avatar-'.$user->id.'.jpg')
                             ->toMediaCollection('avatar');
                    } catch (\Throwable $e) {
                        // Ignorer un éventuel échec de téléchargement d'avatar
                    }
                }

                // Log in the existing user
                Auth::login($user);
                return redirect()->intended('/dashboard');
            }

            // Check if a user with the same email already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // You might want to link the social account to the existing user account here
                // For now, we'll just log them in.

                // Mettre à jour l'avatar si disponible
                if (method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                    try {
                        $user->clearMediaCollection('avatar');
                        $user->addMediaFromUrl($socialUser->getAvatar())
                             ->usingFileName('avatar-'.$user->id.'.jpg')
                             ->toMediaCollection('avatar');
                    } catch (\Throwable $e) {
                        // Ignorer un éventuel échec de téléchargement d'avatar
                    }
                }

                Auth::login($user);
                return redirect()->intended('/dashboard');
            }

            // Create a new user
            $newUser = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'password' => Hash::make(str()->random(24)), // Generate a random password

            ]);

            // Récupérer et associer l'avatar si disponible
            if (method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                try {
                    $newUser->addMediaFromUrl($socialUser->getAvatar())
                            ->usingFileName('avatar-'.$newUser->id.'.jpg')
                            ->toMediaCollection('avatar');
                } catch (\Throwable $e) {
                    // Ignorer un éventuel échec de téléchargement d'avatar
                }
            }

            Auth::login($newUser);
            $newUser->assignRole('visitor');
            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return $e;
            // Handle exceptions, e.g., user denied access
            return redirect('/login')->with('error', 'Une erreur est survenue lors de l\'authentification.');
        }
    }
}
