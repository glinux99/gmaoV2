<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team; // Ajouté pour les filtres si nécessaire
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // Conservations de vos permissions Spatie
        // $this->middleware('can:read-user')->only(['index']);
        // $this->middleware('can:create-user')->only(['store']);
        // $this->middleware('can:update-user')->only(['update']);
        // $this->middleware('can:delete-user')->only(['destroy', 'bulkDestroy']);
        // $this->middleware('can:impersonate-user')->only(['impersonate', 'leaveImpersonate']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $teamId = $request->input('team_id');

        // Ajout de la relation 'team' en plus de 'roles'
        $query = User::with(['roles', 'team'])->latest()->where(function (Builder $query) use ($search, $teamId) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', '%' . $search . '%'));
                });
            }

            if ($teamId) {
                $query->where('team_id', $teamId);
            }
        });

        $users = $query->paginate($perPage)->withQueryString();

        if ($request->expectsJson()) {
            return $users;
        }

        return Inertia::render('User/Users', [
            'title' => 'Gestion des Utilisateurs',
            'users' => $users,
            'roles' => Role::get(['id', 'name']),
            'teams' => Team::get(['id', 'name']), // Passé à la vue pour les filtres
            'filters' => $request->only(['search', 'role', 'team_id', 'per_page']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Password::defaults()],
            'team_id' => 'nullable|exists:teams,id',
            'hourly_rate' => 'nullable|numeric|min:0',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'contract_type' => 'nullable|string|max:50',
            'hiring_date' => 'nullable|date',
            'linkedin_url' => 'nullable|url|max:255',
            'bio' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Upload avatar
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Password hash (requis pour store)
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        // Sync roles
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Utilisateur créé'])
            : back()->with('success', 'Utilisateur créé avec succès.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return $request->expectsJson()
            ? response()->json(['errors' => $e->errors()], 422)
            : back()->withErrors($e->errors());
    } catch (\Throwable $th) {
        return $request->expectsJson()
            ? response()->json(['error' => 'Erreur serveur'], 500)
            : back()->with('error', 'Erreur lors de la création.');
    }
}

public function update(Request $request, User $user)
{
    // SUPPRESSION DU return $request; ICI

    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', Password::defaults()],
            'team_id' => 'nullable|exists:teams,id',
            'hourly_rate' => 'nullable|numeric|min:0',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'contract_type' => 'nullable|string|max:50',
            'hiring_date' => 'nullable|date',
            'linkedin_url' => 'nullable|url|max:255',
            'bio' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $updateData = collect($validated)->except(['password', 'roles', 'avatar'])->toArray();

        // Password optionnel
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $updateData['avatar'] = null;
        }

        $user->update($updateData);

        // Sync roles
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Utilisateur mis à jour'])
            : back()->with('success', 'Utilisateur mis à jour.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return $request->expectsJson()
            ? response()->json(['errors' => $e->errors()], 422)
            : back()->withErrors($e->errors());
    } catch (\Throwable $th) {
        return $request->expectsJson()
            ? response()->json(['error' => 'Erreur serveur'], 500)
            : back()->with('error', 'Erreur lors de la mise à jour.');
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        // Suppression de l'avatar physique
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $ids = $request->input('ids');

        // Empêcher l'utilisateur de se supprimer lui-même
        if (in_array(auth()->id(), $ids)) {
            return back()->withErrors(['error' => 'Opération non autorisée. Vous ne pouvez pas vous supprimer.']);
        }

        // Suppression des avatars physiques avant la suppression en BDD
        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        User::whereIn('id', $ids)->delete();

        return back()->with('success', 'Les utilisateurs sélectionnés ont été supprimés.');
    }

    /**
     * Impersonate the given user.
     */
    public function impersonate(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas usurper votre propre identité.']);
        }

        session(['impersonator_id' => Auth::id()]);
        Auth::login($user);

        return Redirect::route('dashboard');
    }

    /**
     * Revert to the original user.
     */
    public function leaveImpersonate()
    {
        $impersonatorId = session('impersonator_id');
        Auth::login(User::findOrFail($impersonatorId));
        session()->forget('impersonator_id');

        return Redirect::route('user.index');
    }
}
