<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Equipment;
use App\Models\User;
use App\Models\Team;
use App\Models\Region;
use App\Models\SparePart;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceOrder;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use PhpParser\Node\Stmt\TryCatch;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function getDepartmentsData()
    {
        return [
            [
                "label" => "Direction",
                "children" => [
                    ["label" => "Directeur Général"],
                    ["label" => "Directeur Exécutif"]
                ]
            ],
            [
                "label" => "Départements Rattachés",
                "children" => [
                    ["label" => "Département Affaires Publiques & Communautés"],
                    ["label" => "Service Affaires Juridiques"]
                ]
            ],
            [
                "label" => "Pôles Opérationnels & Supports",
                "children" => [
                    ["label" => "Directeur Construction Centrales"],
                    ["label" => "Chef du Département Construction Réseau"],
                    [
                        "label" => "Directeur HSE",
                        "children" => [
                            ["label" => "Département HSE"],
                            ["label" => "Service Médical (VF)"]
                        ]
                    ],
                    [
                        "label" => "Directeur des Opérations",
                        "children" => [
                            ["label" => "Sites Support Logistique"],
                            ["label" => "Centrales Production"],
                            ["label" => "Réseaux"],
                            ["label" => "Technologie Informatique"]
                        ]
                    ],
                    [
                        "label" => "Chef Département Commercial",
                        "children" => [
                            ["label" => "Service Commercial"],
                            ["label" => "Service Communication"],
                            ["label" => "Service Centre d'Appels"]
                        ]
                    ],
                    [
                        "label" => "Directeur Administratif & Financier",
                        "children" => [
                            ["label" => "Département Finances-Admi-Taxes"],
                            ["label" => "Département Contrôle de Gestion & Trésorerie"],
                            ["label" => "Département Achat-Logistique"]
                        ]
                    ],
                    ["label" => "Chef de Département Ressources Humaines"]
                ]
            ]
        ];
    }
    public function index(Request $request)
    {
        $tasks = Task::with(['assignable', 'equipments', 'instructions', 'team', 'region'])
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
            }) // Le filtre de date ne s'applique que si les dates sont fournies
            ->when($request->input('search'), function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);
              // Transformer l'arbre d'équipements pour le TreeSelect
        $equipmentTree = Equipment::whereNull('parent_id')->with('children.children')->get();
        $transformedEquipmentTree = $this->transformForTreeSelect($equipmentTree);
        return Inertia::render('Tasks/Tasks', [
            'tasks' => $tasks,
            'filters' => $request->only('search'),
            'equipments' => Equipment::all(),
            'users' => User::all(),
            'teams' => Team::all(),
            'regions' => Region::all(),
            'departments'=>$this->getDepartmentsData(),
            'spareParts'=>SparePart::all(),
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
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            'maintenance_type' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'time_spent' => 'nullable|integer',
            'estimated_cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',
            'jobber'=> 'nullable|integer',
            'related_equipments'=>'nullable',
                // Validation corrigée: permet des entiers ou des chaînes pour les IDs
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'nullable|numeric|exists:equipment,id', // Assure que c'est un nombre ET qu'il existe
                     // Instructions
            'instructions' => 'nullable|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string',
            'instructions.*.equipment_id' => 'nullable|numeric|exists:equipment,id',
            'instructions.*.task_id' => 'nullable|numeric|exists:tasks,id',
            'instructions.*.is_required' => 'boolean',
            // Champs pour ServiceOrder
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
        ]);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();

            try {
                 $task = Task::create(\Illuminate\Support\Arr::except($validatedData, ['equipment_ids']));
                if (isset($validatedData['equipment_ids'])) {
                    $equipmentIds = collect($validatedData['equipment_ids'])->map(fn($id) => (int) $id)->toArray();
                    $task->equipments()->attach($equipmentIds);
                }
            } catch (\Throwable $th) {
                //throw $th;
                DB::rollBack();
                return $th;
            }

           try {
                $activityData = [
 'task_id' => $task->id,
 'maintenance_id' => null, // Not applicable for tasks
 'intervention_request_id' => null, // Not applicable for tasks
 'user_id' => Auth::id(), // The user who created the activity record
 'actual_start_time' => $validatedData['planned_start_date'] ?? null,
 'parent_id' => null, // Not a sub-activity
 'actual_end_time' => $validatedData['planned_end_date'] ?? null,
 'assignable_type' => $validatedData['assignable_type'] ?? null,
 'assignable_id' => $validatedData['assignable_id'] ?? null,
 'jobber' => $validatedData['jobber'] ?? null,
 'spare_parts_used' => null, // To be updated later
 'spare_parts_returned' => null, // To be updated later
 'status' => $validatedData['status'] ?? 'pending',
 'problem_resolution_description' => $validatedData['description'] ?? null,
 'proposals' => null, // To be updated later
 'additional_information' => null, // To be updated later
 'equipment_id' => $task->equipments->first()->id ?? null, // First equipment if exists
 'title' => "Activité sur l'Equipement Tag - ".$task->equipments->first()->tag." - ".$task->equipments->first()->designation ?? $task->title, // Equipment name or task title
 'region_id' => $validatedData['region_id'] ?? null,
 'zone_id' => null, // To be updated later
                ];
                // dd($activityData);
 Activity::create($activityData);
    // dd(123);
           } catch (\Throwable $th) {
            //throw $th;
            return $th;
           }

            if (isset($validatedData['instructions'])) {
                foreach ($validatedData['instructions'] as $instructionData) {

                    $task->instructions()->create(array_merge($instructionData, ['equipment_id' => $instructionData['equipment_id']]));
                }
            }

            // Créer une ServiceOrder si un coût est fourni
            if (isset($validatedData['service_order_cost']) && $validatedData['service_order_cost'] > 0) {
                $serviceOrder = ServiceOrder::create([
                    'task_id' => $task->id,
                    'description' => $validatedData['service_order_description'] ?? 'Prestation liée à la tâche #' . $task->id,
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

            return redirect()->route('tasks.index')->with('success', 'Tâche créée avec succès.');

        } catch (\Exception $e) {
            return $e;
            DB::rollBack();
            Log::error('Erreur lors de la création de la tâche: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création de la tâche. ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            'maintenance_type' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'time_spent' => 'nullable|integer',
            'estimated_cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',
            "related_equipments"=> "nullable",
                // Validation corrigée: permet des entiers ou des chaînes pour les IDs
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'nullable|numeric|exists:equipment,id', // Assure que c'est un nombre ET qu'il existe
                // Instructions
            'instructions' => 'nullable|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string',
            'instructions.*.equipment_id' => 'nullable|numeric|exists:equipment,id',
            'instructions.*.task_id' => 'nullable|numeric|exists:tasks,id',
            'instructions.*.is_required' => 'boolean',
            // Champs pour ServiceOrder
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
        ]);
            // return $request;
        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
          try {

            // Update the associated activity if it exists


            $validatedData = $validator->validated();

        $task->update(\Illuminate\Support\Arr::except($validatedData, ['equipment_ids', 'related_equipments', 'instructions']));
       if ($task->activity && $task->activity->isNotEmpty()) {
       // On recharge la relation pour être sûr d'avoir les données à jour
       $task->load('activity');
       $activity = $task->activity;

    // On boucle sur chaque activité liée à cette tâche
    foreach ($task->activity as $activity) {
       if ($activity) {
           // 1. Propagation de l'assignation (si l'activité n'en a pas déjà une)
           if (($task->wasChanged('assignable_id') || $task->wasChanged('assignable_type')) && is_null($activity->assignable_id)) {
               $activity->assignable_type = $task->assignable_type;
               $activity->assignable_id = $task->assignable_id;
           }

        // 1. Propagation de l'assignation
        // Si l'assignation de la tâche a changé ET que l'activité n'est pas encore assignée
        if (($task->wasChanged('assignable_id') || $task->wasChanged('assignable_type'))
            && is_null($activity->assignable_id)) {
           // 2. Synchronisation des champs opérationnels
           $activity->actual_start_time = $validatedData['planned_start_date'] ?? $task->planned_start_date;
           $activity->actual_end_time = $validatedData['planned_end_date'] ?? $task->planned_end_date;
           $activity->status = $validatedData['status'] ?? $task->status;

            $activity->assignable_type = $task->assignable_type;
            $activity->assignable_id = $task->assignable_id;
        }

        // 2. Synchronisation des champs opérationnels
        // On met à jour les temps réels, le statut et la priorité
        $activity->actual_start_time = $validatedData['planned_start_date'] ?? $task->planned_start_date;
        $activity->actual_end_time = $validatedData['planned_end_date'] ?? $task->planned_end_date;

        // Note : On ne synchronise le statut de l'activité que si nécessaire
        $activity->status = $validatedData['status'] ?? $task->status;
        // $activity->priority = $validatedData['priority'] ?? $task->priority;

        // 3. Sauvegarde de l'instance individuelle
        $activity->save();
    }
}
           // 3. Sauvegarde de l'activité mise à jour
           $activity->save();
       }
    // 2. Maintenant que $task a un ID, synchronisez les équipements.
    // La méthode sync s'occupera d'ajouter/supprimer les entrées dans la table pivot.
     $equipmentIds = [];
    if (isset($validatedData['related_equipments']) && is_array($validatedData['related_equipments'])) {
        foreach ($validatedData['related_equipments'] as $equipmentId => $state) {
            if ($state['checked']) {
                $equipmentIds[] = $equipmentId;
            }
        }
    }

    // Utiliser la liste d'equipmentIds pour synchroniser la relation
    $task->equipments()->sync($equipmentIds);


    if (isset($validatedData['equipment_ids'])) {
        $task->equipments()->sync($validatedData['equipment_ids']);
    }

            // // Update instructions
            if (isset($validatedData['instructions'])) {

                $task->instructions()->where('task_id', $task->id)->delete(); // Delete existing instructions
                foreach ($validatedData['instructions'] as $instructionData) {

                    $task->instructions()->create([
                        'equipment_id' => $instructionData['equipment_id'], // Assuming equipment_id is part of instructionData
                        'label' => $instructionData['label'],
                        'type' => $instructionData['type'],
                        'is_required' => $instructionData['is_required'],
                    ]);
                }
            } else {
                $task->instructions()->delete();
            }

            // Mettre à jour ou créer la ServiceOrder
            $serviceOrder = ServiceOrder::where('task_id', $task->id)->first();
            if (isset($validatedData['service_order_cost']) && $validatedData['service_order_cost'] > 0) {
                $serviceOrderData = [
                    'task_id' => $task->id,
                    'description' => $validatedData['service_order_description'] ?? 'Prestation liée à la tâche #' . $task->id,
                    'cost' => $validatedData['service_order_cost'],
                    'status' => 'completed',
                    'order_date' => now(),
                    'actual_completion_date' => now(),
                ];
                $serviceOrder = ServiceOrder::updateOrCreate(['task_id' => $task->id], $serviceOrderData);

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

            return redirect()->route('tasks.index')->with('success', 'Tâche mise à jour avec succès.');
        } catch (\Exception $e) {
            return $e;
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la tâche: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour de la tâche.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        DB::beginTransaction();
        try {
            // Delete associated activities first
            $serviceOrder = ServiceOrder::where('task_id', $task->id)->first();
            if ($serviceOrder) {
                // Supprimer les dépenses associées à la ServiceOrder
                $serviceOrder->expenses()->delete();
                // Supprimer la ServiceOrder elle-même
                $serviceOrder->delete();
            }
            if ($task->activity) {
                $task->activity->delete();
            }

            $task->instructions()->delete(); // Delete associated instructions first
            $task->equipments()->detach(); // Detach all associated equipments
            $task->delete();
            DB::commit();
            return redirect()->route('tasks.index')->with('success', 'Tâche supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de la tâche: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de la tâche.');
        }
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        DB::beginTransaction();
        try {
            $tasks = Task::whereIn('id', $request->ids)->get();

            foreach ($tasks as $task) {
                // Supprimer les ServiceOrders et leurs dépenses associées
                $task->serviceOrders()->each(function ($serviceOrder) {
                    $serviceOrder->expenses()->delete();
                    $serviceOrder->delete();
                });

                $task->activity()->delete();
                $task->instructions()->delete();
                $task->equipments()->detach();
                $task->delete();
            }

            DB::commit();
            return redirect()->route('tasks.index')->with('success', 'Les tâches sélectionnées ont été supprimées avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression en masse des tâches: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression des tâches.');
        }
    }
}
