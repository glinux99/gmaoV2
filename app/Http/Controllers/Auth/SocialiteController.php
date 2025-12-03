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
                // Log in the existing user
                Auth::login($user);
                return redirect()->intended('/dashboard');
            }

            // Check if a user with the same email already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // You might want to link the social account to the existing user account here
                // For now, we'll just log them in.
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

            // Assign a default role, e.g., 'student'


            Auth::login($newUser);
             $newUser->assignRole('visitor');
            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            // Handle exceptions, e.g., user denied access
            dd($e);
            return redirect('/login')->with('error', 'Une erreur est survenue lors de l\'authentification.');
        }
    }
}
