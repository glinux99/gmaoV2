<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Team;
use App\Models\User;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceOrder;
use Inertia\Inertia;
use App\Models\MaintenanceInstruction;
use App\Models\SparePart;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Affiche la liste des maintenances.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $maintenancesQuery = Maintenance::with(['assignable', 'equipments', 'instructions.equipment'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('scheduled_start_date', [$startDate, $endDate]);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        // Transformer l'arbre d'équipements pour le TreeSelect
        $equipmentTree = Equipment::whereNull('parent_id')->with('children.children')->get();
        $transformedEquipmentTree = $this->transformForTreeSelect($equipmentTree);

        return Inertia::render('Tasks/Maintenances', [
            'maintenances' => $maintenancesQuery,
            'filters' => $request->only('search'),
            'equipments' => Equipment::all(), // Assumant que les équipements sont disponibles pour lier les activités
            'users' => User::all(), // Assumant que les utilisateurs sont disponibles pour lier les activités
            'teams' => Team::all(), // Assumant que les équipes sont disponibles pour lier les activités
            'regions' => Region::all(), // Assumant que les régions sont disponibles pour lier les activités
            'tasks' => [], // Assumant que les tâches sont disponibles pour lier les activités
            'spareParts' => SparePart::all(), // Requis pour la sélection de pièces
            'equipmentTree' => $transformedEquipmentTree,
        ]);
    }

    private function transformForTreeSelect($equipments)
    {
        return $equipments->map(function ($equipment) {
            $children = [];
            if ($equipment->children->isNotEmpty()) {
                $children = $this->transformForTreeSelect($equipment->children);
            }

            return [
                'id' => $equipment->id,
                'key' => (string) $equipment->id,
                'label' => $equipment->designation,
                'children' => $children,
            ];
        });
    }

    // ------------------------------------------------------------------------------------------

    /**
     * Enregistre une nouvelle maintenance.
     */
   public function store(Request $request)
{
    // Log::info('Requête reçue pour store:', $request->all()); // Décommenter pour debug

//  $maintenance = Maintenance::create($request->all());

    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',

        // Validation corrigée: permet des entiers ou des chaînes pour les IDs
        'equipment_ids' => 'required|array',
        'equipment_ids.*' => 'required|numeric|exists:equipment,id', // Assure que c'est un nombre ET qu'il existe

        'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
        'assignable_id' => 'nullable|integer',
        'type' => 'nullable|string',
        'status' => 'nullable|string',
        'priority' => 'nullable|string',
        // Utiliser 'date' seulement (Laravel est assez souple)
        'scheduled_start_date' => 'nullable|date',
        'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
        'estimated_duration' => 'nullable|integer',
        'cost' => 'nullable|numeric',
        'region_id' => 'nullable|exists:regions,id',

        // Champs de récurrence
        'recurrence_type' => 'nullable|string',
        'recurrence_interval' => 'nullable|integer',
        'recurrence_days' => 'nullable|array',
        'recurrence_day_of_month' => 'nullable|integer',
        'recurrence_month' => 'nullable|integer',
        'reminder_days' => 'nullable|integer',

        // Instructions
        'node_instructions' => 'nullable|array',
        'node_instructions.*.*.label' => 'required|string|max:255',
        'node_instructions.*.*.type' => 'required|string',
        'node_instructions.*.*.is_required' => 'boolean',

        // Champs pour ServiceOrder
        'service_order_cost' => 'nullable|numeric|min:0',
        'service_order_description' => 'nullable|string|required_with:service_order_cost',
    ]);

    if ($validator->fails()) {

        // Log::error('Erreur de validation:', $validator->errors()->toArray()); // Décommenter pour debug
        return redirect()->back()->withErrors($validator)->withInput();
    }

    DB::beginTransaction();
    try {
        $validatedData = $validator->validated();

        // Création de l'enregistrement principal
        $maintenance = Maintenance::create($validatedData);

        // 1. Attacher les équipements (relation Many-to-Many via table pivot)
        // Les IDs doivent être des entiers. On s'assure de la conversion si l'on soupçonne des chaînes.
        $equipmentIds = collect($validatedData['equipment_ids'])->map(fn($id) => (int) $id)->toArray();
        $maintenance->equipments()->attach($equipmentIds);

        // 2. Enregistrer les instructions (relation HasMany)
        if (isset($validatedData['node_instructions'])) {
            foreach ($validatedData['node_instructions'] as $equipmentId => $instructions) {
                // S'assurer que equipmentId est un entier pour l'enregistrement
                $cleanEquipmentId = (int) $equipmentId;
                foreach ($instructions as $instructionData) {
                    // Crée l'instruction et l'associe à la maintenance et à l'équipement
                    $maintenance->instructions()->create(array_merge($instructionData, ['equipment_id' => $cleanEquipmentId]));
                }
            }
        }

        // Créer une ServiceOrder si un coût est fourni
        if (isset($validatedData['service_order_cost']) && $validatedData['service_order_cost'] > 0) {
            $serviceOrder = ServiceOrder::create([
                'task_id' => null, // Maintenance n'a pas de task_id direct, à adapter si nécessaire
                'maintenance_id' => $maintenance->id, // Lier à la maintenance
                'description' => $validatedData['service_order_description'] ?? 'Prestation liée à la maintenance #' . $maintenance->id,
                'cost' => $validatedData['service_order_cost'],
                'status' => 'completed',
                'order_date' => now(),
                'actual_completion_date' => now(),
            ]);

            $serviceOrder->expenses()->create([
                'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                'amount' => $serviceOrder->cost,
                'expense_date' => now(),
                'category' => 'external_service',
                'user_id' => Auth::id(),
                'notes' => 'Dépense générée automatiquement pour la prestation de service.',
                'status' => 'pending',
            ]);
        }

        DB::commit();

        // Log::info('Maintenance créée avec succès.', ['id' => $maintenance->id]); // Décommenter pour debug
        return redirect()->route('maintenances.index')->with('success', 'Maintenance créée avec succès.');

    } catch (\Exception $e) {
        DB::rollBack();
        return $e;
        Log::error('Erreur lors de la création de la maintenance: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        return redirect()->back()->with('error', 'Une erreur est survenue lors de la création de la maintenance. ' . $e->getMessage()); // Retourner le message d'erreur en mode développement
    }
}

    // ------------------------------------------------------------------------------------------

    /**
     * Met à jour une maintenance existante.
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'exists:equipment,id',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            // On peut garder 'required' si ces champs doivent toujours être présents lors de la mise à jour
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'estimated_duration' => 'nullable|integer',
            'cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',

            // Champs de récurrence
            'recurrence_type' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer',
            'recurrence_days' => 'nullable|array',
            'recurrence_day_of_month' => 'nullable|integer',
            'recurrence_month' => 'nullable|integer',
            'reminder_days' => 'nullable|integer',

            // Instructions
            'node_instructions' => 'nullable|array',
            'node_instructions.*.*.label' => 'required|string|max:255',
            'node_instructions.*.*.type' => 'required|string',
            'node_instructions.*.*.is_required' => 'boolean',

            // Champs pour ServiceOrder
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $maintenance->update($validator->validated());

            // Mettre à jour les équipements liés (synchronisation Many-to-Many)
            $maintenance->equipments()->sync($request->input('equipment_ids'));

            // Mettre à jour les instructions
            $maintenance->instructions()->delete(); // Supprimer les anciennes instructions
            if ($request->has('node_instructions')) {
                foreach ($request->input('node_instructions') as $equipmentId => $instructions) {
                    foreach ($instructions as $instructionData) {
                        $maintenance->instructions()->create([
                            'equipment_id' => $equipmentId,
                            'label' => $instructionData['label'],
                            'type' => $instructionData['type'],
                            'is_required' => $instructionData['is_required'],
                        ]);
                    }
                }
            }

            // Mettre à jour ou créer la ServiceOrder
            $serviceOrder = ServiceOrder::where('maintenance_id', $maintenance->id)->first();
            if (isset($validator->validated()['service_order_cost']) && $validator->validated()['service_order_cost'] > 0) {
                $serviceOrderData = [
                    'maintenance_id' => $maintenance->id,
                    'description' => $validator->validated()['service_order_description'] ?? 'Prestation liée à la maintenance #' . $maintenance->id,
                    'cost' => $validator->validated()['service_order_cost'],
                    'status' => 'completed',
                    'order_date' => now(),
                    'actual_completion_date' => now(),
                ];
                $serviceOrder = ServiceOrder::updateOrCreate(['maintenance_id' => $maintenance->id], $serviceOrderData);

                // Supprimer les anciennes dépenses liées à cette ServiceOrder pour éviter les doublons
                $serviceOrder->expenses()->delete();
                $serviceOrder->expenses()->create([
                    'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                    'amount' => $serviceOrder->cost,
                    'expense_date' => now(),
                    'category' => 'external_service',
                    'user_id' => Auth::id(),
                    'notes' => 'Dépense générée automatiquement pour la prestation de service.',
                    'status' => 'pending',
                ]);
            } else if ($serviceOrder) {
                // Si le coût est à 0 ou non fourni et qu'une ServiceOrder existait, la supprimer
                $serviceOrder->expenses()->delete();
                $serviceOrder->delete();
            }

            DB::commit();

            return redirect()->route('maintenances.index')->with('success', 'Maintenance mise à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour de la maintenance.');
        }
    }

    // ------------------------------------------------------------------------------------------

    /**
     * Supprime une maintenance.
     */
    public function destroy(Maintenance $maintenance)
    {
        DB::beginTransaction();
        try {
            $maintenance->expenses()->delete(); // Supprimer les dépenses associées
            $maintenance->equipments()->detach(); // Détacher les équipements
            $maintenance->instructions()->delete(); // Supprimer les instructions
            $maintenance->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de la maintenance: ' . $e->getMessage());
        }
        return redirect()->route('maintenances.index')->with('success', 'Maintenance supprimée avec succès.');
    }
}
