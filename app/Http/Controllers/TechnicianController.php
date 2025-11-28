<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\User; // Assuming Technician is now represented by the User model
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Only retrieve users with the 'technician' role
        $query = User::role('technician')->with('region');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->orWhere('fonction', 'like', '%' . $request->search . '%')
                ->orWhere('numero', 'like', '%' . $request->search . '%')
                ->orWhere('region', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Teams/Technicians', [
            'technicians' => $query->get(),
            'regions'=> Region::get(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Technicians');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Assign the 'technician' role by default
        $validated['role'] = 'technician';

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('avatars', 'public');
            $validated['profile_photo'] ='storage/'.$path;
        }

        $technician = User::create($validated);
        $technician->assignRole('technician');



        return redirect()->route('technicians.index')->with('success', 'Technicien créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $technician)
    {
        // Not typically used for Inertia CRUD, but can be implemented if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $technician)
    {
        return Inertia::render('Technicians', [
            'technician' => $technician,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $technician)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $technician->id,
            'password' => 'nullable|string|min:8', // Password can be null if not changed
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validated['password']) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']); // Don't update password if it's empty
        }

        if ($request->hasFile('profile_photo')) {
            if ($technician->profile_photo_path) {
                Storage::disk('public')->delete($technician->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('avatars', 'public');
            $validated['profile_photo'] = $path;
        }

        $technician->update($request->except('profile_photo'));

        return redirect()->route('technicians.index')->with('success', 'Technicien mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $technician)
    {
        if ($technician->profile_photo_path) {
            Storage::disk('public')->delete($technician->profile_photo_path);
        }
        $technician->delete();
        return redirect()->route('technicians.index')->with('success', 'Technicien supprimé avec succès.');
    }
}
