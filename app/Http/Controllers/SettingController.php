<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Affiche l'écran des paramètres du profil de l'utilisateur.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        $logins = $user->logins()->latest()->paginate(10);

        return Inertia::render('Configurations/Users/Settings', [
            'user' => $user,
            'logins' => $logins,
            "regions" => Region::get(['id', 'designation']),
        ]);
    }

    /**
     * Met à jour les informations de profil de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
  public function updateProfile(Request $request)
{
    /** @var \App\Models\User $user */

        $user = Auth::user();

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'fonction' => 'nullable|string|max:255',
        'numero' => 'nullable|string|max:255',
        'region_id' => 'nullable|exists:regions,id',
        'pointure' => 'nullable|numeric',
        'size' => 'nullable|numeric',
        'profile_photo' => 'nullable|image|max:2048', // Augmenté à 2Mo pour plus de confort
    ]);

    // 1. Gestion de la photo avec Spatie MediaLibrary
    if ($request->hasFile('profile_photo')) {
        // Supprime l'ancienne photo et ajoute la nouvelle dans la collection 'avatar'
        $user->addMediaFromRequest('profile_photo')
             ->toMediaCollection('avatar');
    }

    // 2. Mise à jour des autres champs
    // On retire 'profile_photo' de l'array pour éviter les erreurs SQL
    $user->update(collect($validated)->except('profile_photo')->toArray());

    return back()->with('message', 'Profil mis à jour avec succès.');
}

    /**
     * Met à jour le mot de passe de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
   public function updatePassword(Request $request)
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

    $validated = $request->validate([
        // 'current_password' vérifie automatiquement que le mot de passe correspond à celui en DB
        'current_password' => ['required', 'string', 'current_password'],
        'password' => [
            'required',
            'string',
            Password::defaults()->mixedCase()->symbols(), // Sécurité renforcée
            'confirmed'
        ],
    ]);

    // Utilisation de la méthode update() simple ou forceFill
    $user->update([
        'password' => Hash::make($validated['password']),
    ]);

    return back()->with('message', 'Votre mot de passe a été modifié avec succès.');
}

    /**
     * Déconnecte une session utilisateur spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $sessionId L'ID de la session à déconnecter.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logoutSession(Request $request, $sessionId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // On s'assure que la session appartient bien à l'utilisateur connecté
        // et qu'il ne s'agit pas de sa session actuelle.
        if ($sessionId === $request->session()->getId()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas déconnecter votre session actuelle.']);
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('message', 'La session a été fermée avec succès.');
    }

    /**
     * Supprime le compte de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Votre compte a été supprimé.');
    }
}
