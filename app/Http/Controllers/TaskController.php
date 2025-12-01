<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Equipment;
use App\Models\User;
use App\Models\Team;
use App\Models\Region;
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
    public function index(Request $request)
    {
        $tasks = Task::with(['assignable', 'equipments', 'instructions', 'team', 'instructions', 'region'])
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
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'time_spent' => 'nullable|integer',
            'cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',
                // Validation corrigée: permet des entiers ou des chaînes pour les IDs
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'nullable|numeric|exists:equipment,id', // Assure que c'est un nombre ET qu'il existe
                // Instructions
            'instructions' => 'nullable|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string',
            'instructions.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();

            try {
                 $task = Task::create($validatedData);
                $equipmentIds = collect($validatedData['equipment_ids'])->map(fn($id) => (int) $id)->toArray();
            $task->equipments()->attach($equipmentIds);
            } catch (\Throwable $th) {
                //throw $th;
                return $th;
            }

            if (isset($validatedData['instructions'])) {
                foreach ($validatedData['instructions'] as $instructionData) {
                    $task->instructions()->create(array_merge($instructionData, ['equipment_id' => $validatedData['equipment_id']]));
                }
            }

            DB::commit();

            return redirect()->route('tasks.index')->with('success', 'Tâche créée avec succès.');

        } catch (\Exception $e) {
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            'maintenance_type' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'time_spent' => 'nullable|integer',
            'cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',
                // Validation corrigée: permet des entiers ou des chaînes pour les IDs
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'nullable|numeric|exists:equipment,id', // Assure que c'est un nombre ET qu'il existe
                // Instructions
            'instructions' => 'nullable|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string',
            'instructions.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {

            $validatedData = $validator->validated();

            // Update the main task record
            $task->update($validatedData);

            // Sync equipments (Many-to-Many relationship)
            $task->equipments()->sync($request->input('equipment_ids'));

            // Update instructions
            if (isset($validatedData['instructions'])) {
                $task->instructions()->delete(); // Delete existing instructions
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
