<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TechnicianImport;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('technician')->with(['teams', 'region'])->latest();

        // --- Gestion des filtres et de la recherche ---
        $filters = $request->input('filters', []);

        // Filtre global
        if (isset($filters['global']['value'])) {
            $globalFilter = $filters['global']['value'];
            $query->where(function ($q) use ($globalFilter) {
                $q->where('name', 'like', "%{$globalFilter}%")
                  ->orWhere('email', 'like', "%{$globalFilter}%")
                  ->orWhere('fonction', 'like', "%{$globalFilter}%")
                  ->orWhere('numero', 'like', "%{$globalFilter}%")
                  ->orWhereHas('region', fn($subQ) => $subQ->where('designation', 'like', "%{$globalFilter}%"));
            });
        }

        // Filtres par colonne
        if (isset($filters['name']['constraints'][0]['value'])) {
            $query->where('name', 'like', $filters['name']['constraints'][0]['value'] . '%');
        }
        if (isset($filters['fonction']['value'])) {
            $query->whereIn('fonction', $filters['fonction']['value']);
        }
        if (isset($filters['region.designation']['value'])) {
            $query->whereHas('region', fn($q) => $q->whereIn('designation', $filters['region.designation']['value']));
        }
        if (isset($filters['email']['constraints'][0]['value'])) {
            $query->where('email', 'like', $filters['email']['constraints'][0]['value'] . '%');
        }
        if (isset($filters['numero']['constraints'][0]['value'])) {
            $query->where('numero', 'like', '%' . $filters['numero']['constraints'][0]['value'] . '%');
        }

        // --- Gestion du tri ---
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder') === '1' ? 'asc' : 'desc';

        if (str_contains($sortField, '.')) {
            // Tri sur une relation (ex: region.designation)
            [$relation, $field] = explode('.', $sortField);
            if ($relation === 'region') {
                $query->join('regions', 'users.region_id', '=', 'regions.id')
                      ->orderBy("regions.{$field}", $sortOrder)
                      ->select('users.*'); // Important pour éviter les conflits d'ID
            }
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        return Inertia::render('Teams/Technicians', [
            'technicians' => $query->paginate($request->input('per_page', 15))->withQueryString(),
            'regions' => Region::all(['id', 'designation']),
            'filters' => $filters,
            'queryParams' => $request->all([
                'page',
                'rows',
                'sortField',
                'sortOrder',
                'filters'
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            // 'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|max:2048', // This field is likely for a direct path, consider using Spatie Media Library for file uploads
            'region_id' => 'nullable|exists:regions,id',
            // 'zone_id' => 'nullable|exists:zones,id',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'fonction' => $request->fonction,
                'numero' => $request->numero,
                // 'region' => $request->region,
                'pointure' => $request->pointure,
                'size' => $request->size,
                'region_id' => $request->region_id,
                // 'zone_id' => $request->zone_id,
            ]);

            $user->assignRole('technician');

            if ($request->hasFile('profile_photo')) {
                $user->addMediaFromRequest('profile_photo')->toMediaCollection('avatar');
            }
        });

        return redirect()->route('technicians.index')->with('success', 'Technicien créé.');
    }

    public function update(Request $request, User $technician)
    {

        // return $request;
            $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $technician->id,
            'fonction' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'pointure' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|max:2048', // This field is likely for a direct path, consider using Spatie Media Library for file uploads
            'region_id' => 'nullable|exists:regions,id',
            // 'zone_id' => 'nullable|exists:zones,id',
        ]);

        DB::transaction(function () use ($request, $technician) {
            $technician->update($request->except('profile_photo'));

            if ($request->hasFile('profile_photo')) {
                $technician->clearMediaCollection('avatar');
                $technician->addMediaFromRequest('profile_photo')->toMediaCollection('avatar');
            }
        });

        return redirect()->route('technicians.index')->with('success', 'Technicien mis à jour.');
    }

    public function destroy(User $technician)
    {
        $technician->delete();
        return redirect()->route('technicians.index')->with('success', 'Technicien supprimé.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        User::whereIn('id', $request->ids)->delete();
        return redirect()->route('technicians.index')->with('success', 'Techniciens supprimés.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        try {
            Excel::import(new TechnicianImport, $request->file('file'));

            return redirect()->route('technicians.index')
                ->with('success', "L'importation a été effectuée avec succès.");

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return back()->with('import_errors', $errors)->with('error', 'Certaines lignes n\'ont pas pu être importées.');
        } catch (\Exception $e) {
            Log::error("Erreur d'importation des techniciens: " . $e->getMessage());
            return back()->with('error', "Une erreur est survenue lors de l'importation: " . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Logique d'exportation à implémenter
        // Vous pouvez utiliser une librairie comme Maatwebsite/Excel pour générer le fichier
        return redirect()->back()->with('info', 'La fonctionnalité d\'exportation est en cours de développement.');
    }
}
