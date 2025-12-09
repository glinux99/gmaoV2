<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\User; // Technician is represented by the User model
use Illuminate\Http\Request;
use Inertia\Inertia;

class TechnicianController extends Controller
{
    /**
     * Display a listing of the resource with search and pagination.
     */
    public function index(Request $request)
    {
        // Only technicians
        $query = User::role('technician')->with('region');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('fonction', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $technicians = $query->paginate(10)->withQueryString();

        return Inertia::render('Teams/Technicians', [
            'technicians' => $technicians,
            'regions' => Region::get(['id','designation']),
            'filters' => $request->only(['search']),
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
            'password' => 'required|string|min:8',
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'extra_attributes' => 'nullable|array',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        $technician = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'fonction' => $validated['fonction'] ?? null,
            'numero' => $validated['numero'] ?? null,
            'region' => $validated['region'] ?? null,
            'pointure' => $validated['pointure'] ?? null,
            'size' => $validated['size'] ?? null,
            'extra_attributes' => $validated['extra_attributes'] ?? null,
        ]);

        $technician->assignRole('technician');

        // Avatar via Spatie Media
        if ($request->hasFile('profile_photo')) {
            try {
                $technician->addMediaFromRequest('profile_photo')
                    ->usingFileName('avatar-'.$technician->id.'.jpg')
                    ->toMediaCollection('avatar');
            } catch (\Throwable $e) {
                // ignore upload failure
            }
        }

        return redirect()->route('technicians.index')->with('success', 'Technicien créé avec succès.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $technician)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $technician->id,
            'password' => 'nullable|string|min:8',
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'extra_attributes' => 'nullable|array',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $technician->update(collect($validated)->except(['profile_photo'])->all());

        // Avatar via Spatie Media
        if ($request->hasFile('profile_photo')) {
            try {
                $technician->clearMediaCollection('avatar');
                $technician->addMediaFromRequest('profile_photo')
                    ->usingFileName('avatar-'.$technician->id.'.jpg')
                    ->toMediaCollection('avatar');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return redirect()->route('technicians.index')->with('success', 'Technicien mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $technician)
    {
        $technician->delete();
        return redirect()->route('technicians.index')->with('success', 'Technicien supprimé avec succès.');
    }

    /**
     * Suppression multiple de techniciens.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:users,id',
        ]);

        User::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('technicians.index')->with('success', 'Techniciens sélectionnés supprimés avec succès.');
    }

    /**
     * Export CSV des techniciens (respecte le filtre de recherche).
     */
    public function export(Request $request)
    {
        $filename = 'technicians_export_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');
            // Headings
            fputcsv($handle, ['id','name','email','fonction','numero','region','pointure','size']);

            $query = User::role('technician');
            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('fonction', 'like', "%{$search}%")
                      ->orWhere('numero', 'like', "%{$search}%")
                      ->orWhere('region', 'like', "%{$search}%");
                });
            }

            $query->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $u) {
                    fputcsv($handle, [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->fonction,
                        $u->numero,
                        $u->region,
                        $u->pointure,
                        $u->size,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * Import CSV des techniciens.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Impossible de lire le fichier importé.');
        }

        $header = fgetcsv($handle); // first line
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'Fichier CSV invalide.');
        }

        // Expected minimum columns: name, email
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data || empty($data['email']) || empty($data['name'])) {
                continue;
            }

            $user = User::firstOrNew(['email' => $data['email']]);
            $user->name = $data['name'];
            $user->fonction = $data['fonction'] ?? null;
            $user->numero = $data['numero'] ?? null;
            $user->region = $data['region'] ?? null;
            $user->pointure = $data['pointure'] ?? null;
            $user->size = $data['size'] ?? null;
            if (!$user->exists) {
                $user->password = bcrypt('password');
            }
            $user->save();
            $user->syncRoles(['technician']);
        }

        fclose($handle);

        return redirect()->route('technicians.index')->with('success', 'Import des techniciens terminé.');
    }
}
