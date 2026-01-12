<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:read-user')->only(['index']);
        $this->middleware('can:create-user')->only(['store']);
        $this->middleware('can:update-user')->only(['update']);
        $this->middleware('can:delete-user')->only(['destroy', 'bulkDestroy']);
        $this->middleware('can:impersonate-user')->only(['impersonate', 'leaveImpersonate']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $regionId = $request->input('region_id');

        $query = User::with('roles', 'region')->latest()->where(function (Builder $query) use ($search, $regionId) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', '%' . $search . '%'));
                });
            }

            if ($regionId) {
                $query->where('region_id', $regionId);
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
            'regions' => Region::get(['id', 'designation']),
            'filters' => $request->only(['search', 'role', 'region_id', 'per_page']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => 'nullable|string|max:255',
            'fonction' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'pointure' => 'nullable|string|max:10',
            'size' => 'nullable|string|max:10',
            'role' => 'nullable|string|exists:roles,name',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'fonction' => $validated['fonction'],
            'region_id' => $validated['region_id'],
            'pointure' => $validated['pointure'],
            'size' => $validated['size'],
        ]);

        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        if ($request->hasFile('profile_photo')) {
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('avatar');
        }

        return Redirect::route('user.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'phone' => 'nullable|string|max:255',
            'fonction' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'pointure' => 'nullable|string|max:10',
            'size' => 'nullable|string|max:10',
            'role' => 'nullable|string|exists:roles,name',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $updateData = $request->except('password', 'password_confirmation', 'role', 'profile_photo');

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        $user->update($updateData);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        if ($request->hasFile('profile_photo')) {
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('avatar');
        }

        return Redirect::route('user.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return Redirect::back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete();

        return Redirect::route('user.index')->with('success', 'Utilisateur supprimé.');
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

        // Prevent users from deleting themselves in a bulk operation
        if (in_array(auth()->id(), $ids)) {
            return Redirect::back()->withErrors(['error' => 'Opération non autorisée. Vous ne pouvez pas vous supprimer.']);
        }

        User::whereIn('id', $ids)->delete();

        return Redirect::route('user.index')->with('success', 'Les utilisateurs sélectionnés ont été supprimés.');
    }

    /**
     * Impersonate the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonate(Request $request, User $user)
    {
        // You cannot impersonate yourself.
        if ($user->id === Auth::id()) {
            return Redirect::back()->withErrors(['error' => 'Vous ne pouvez pas vous usurper votre propre identité.']);
        }

        // Store the original admin ID in the session.
        session(['impersonator_id' => Auth::id()]);

        // Login as the new user.
        Auth::login($user);

        return Redirect::route('dashboard');
    }

    /**
     * Revert to the original user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveImpersonate()
    {
        $impersonatorId = session('impersonator_id');
        Auth::login(User::findOrFail($impersonatorId));
        session()->forget('impersonator_id');
        return Redirect::route('user.index');
    }
}
