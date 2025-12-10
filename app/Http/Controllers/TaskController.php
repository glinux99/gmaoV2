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
        ]);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();

            try {
                 $task = Task::create(\Illuminate\Support\Arr::except($validatedData, ['equipment_ids']));
                $equipmentIds = collect($validatedData['equipment_ids'])->map(fn($id) => (int) $id)->toArray();
            $task->equipments()->attach($equipmentIds);
            } catch (\Throwable $th) {
                //throw $th;
                DB::rollBack();
                return $th;
            }

           try {

             Activity::create([
                'task_id' => $task['id'],
                'problem_resolution_description' => $validatedData['description'] ?? null,
                'actual_start_time' => $validatedData['planned_start_date'] ?? null,
                'actual_end_time' => $validatedData['planned_end_date'] ?? null,
                'assignable_type' => $validatedData['assignable_type'] ?? null,
                'assignable_id' => $validatedData['assignable_id'] ?? null,
                'jobber' => $validatedData['jobber'], // Assuming jobber is not directly from task creation, or needs to be set later
                'status' => $validatedData['status'] ?? 'pending', // Default status or from validated data
            ]);

           } catch (\Throwable $th) {
            //throw $th;
            return $th;
           }

            if (isset($validatedData['instructions'])) {
                foreach ($validatedData['instructions'] as $instructionData) {

                    $task->instructions()->create(array_merge($instructionData, ['equipment_id' => $instructionData['equipment_id']]));
                }
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
        ]);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {

            // Update the associated activity if it exists


            $validatedData = $validator->validated();

        $task->update(\Illuminate\Support\Arr::except($validatedData, ['equipment_ids']));
               if ($task->activity) {
                $task->activity->update(\Illuminate\Support\Arr::except($validatedData, ['equipment_ids', 'instructions']));
            }
    // 2. Maintenant que $task a un ID, synchronisez les équipements.
    // La méthode sync s'occupera d'ajouter/supprimer les entrées dans la table pivot.
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
}
