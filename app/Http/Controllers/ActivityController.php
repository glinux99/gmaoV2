<?php

namespace App\Http\Controllers;

use App\Models\SparePartTask;
use Inertia\Inertia;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\InstructionAnswer;
use App\Models\SparePart;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Activity::query();

        if (request()->has('search')) {
            $search = request('search');
            $query->where('problem_resolution_description', 'like', '%' . $search . '%')
                ->orWhere('proposals', 'like', '%' . $search . '%')
                ->orWhere('instructions', 'like', '%' . $search . '%')
                ->orWhereHas('task', fn ($q) => $q->where('title', 'like', '%' . $search . '%'))
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        $query->with(['task.instructions', 'instructionAnswers']);

        return Inertia::render('Tasks/MyActivities', [
            'activities' => $query->paginate(10),
            'filters' => request()->only(['search']),
            'users' => \App\Models\User::all(),
            'tasks' => \App\Models\Task::all(),
            'spareParts'=> SparePart ::all()
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method is not typically used for direct creation in Inertia,
        // as the form is usually part of the index page or a modal.
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'nullable|exists:users,id',
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after_or_equal:actual_start_time',
            'parent_id' => 'nullable|exists:activities,id',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            'jobber' => 'nullable|integer|min:1',
            'spare_parts_used' => 'nullable|array',
            'spare_parts_used.*.id' => 'required_with:spare_parts_used|exists:spare_parts,id',
            'spare_parts_used.*.quantity' => 'required_with:spare_parts_used|integer|min:1',
            'spare_parts_returned' => 'nullable|array',
            'spare_parts_returned.*.id' => 'required_with:spare_parts_returned|exists:spare_parts,id',
            'spare_parts_returned.*.quantity' => 'required_with:spare_parts_returned|integer|min:1',
            'status' => 'nullable|string|in:in_progress,completed,suspended,canceled',
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'instructions' => 'nullable|string|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            // Validation pour les réponses aux instructions
            'instruction_answers' => 'nullable|array',
            'instruction_answers.*' => 'nullable|string|max:255', // Permet des valeurs nulles, la validation requise se fait au front-end

        ]);

        DB::beginTransaction();
        try {
            $validated['user_id'] = $validated['user_id'] ?? Auth::id();

            $activity = Activity::create($validated);

            // Logic for updating spare part quantities based on 'used' and 'returned'
            // This would involve iterating through the spare_parts_used and spare_parts_returned arrays
            // and updating the quantities in the SparePart model.
            // Example (simplified):
            if (isset($validated['spare_parts_used']) && is_array($validated['spare_parts_used'])) {
                foreach ($validated['spare_parts_used'] as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart) {
                        $sparePart->quantity -= $part['quantity'];
                        $sparePart->save();
                    }
                }
            }

            if (isset($validated['spare_parts_returned']) && is_array($validated['spare_parts_returned'])) {
                foreach ($validated['spare_parts_returned'] as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart) {
                        $sparePart->quantity += $part['quantity'];
                        $sparePart->save();
                    }
                }
            }

            // Mettre à jour ou créer les réponses aux instructions
            if (isset($validated['instruction_answers'])) {
                foreach ($validated['instruction_answers'] as $instructionId => $value) {
                    InstructionAnswer::updateOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'task_instruction_id' => $instructionId,
                        ],
                        ['value' => $value, 'user_id' => Auth::id()]
                    );
                }
            }
            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité créée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création de l\'activité: ' . $e->getMessage());
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        return Inertia::render('Tasks/MyActivities/Show', [
            'activity' => $activity->load(['task', 'user', 'parent', 'children', 'assignable']),
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity)
    {
        return Inertia::render('Tasks/MyActivities', [
            'activity' => $activity->load(['task', 'user', 'parent', 'assignable']),
            'users' => \App\Models\User::all(),
            'tasks' => \App\Models\Task::all(),
            'teams' => \App\Models\Team::all(),
            'spareParts' => \App\Models\SparePart::all(),
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after_or_equal:actual_start_time',
            'jobber' => 'nullable|string',
            'spare_parts_used' => 'nullable|array',
            'spare_parts_used.*.id' => 'required_with:spare_parts_used|exists:spare_parts,id',
            'spare_parts_used.*.quantity' => 'required_with:spare_parts_used|integer|min:1',
            'spare_parts_returned' => 'nullable|array',
            'spare_parts_returned.*.id' => 'required_with:spare_parts_returned|exists:spare_parts,id',
            'spare_parts_returned.*.quantity' => 'required_with:spare_parts_returned|integer|min:1',
            'status' => 'nullable|string|in:Planifiée,En cours,Terminée,En attente,Annulée,En retard',
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'instructions' => 'nullable|string|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            'instruction_answers' => 'nullable|array',
            'instruction_answers.*' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Revert previous spare part movements
            if ($activity->spare_parts_used && is_array($activity->spare_parts_used)) {
                foreach ($activity->spare_parts_used as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart && isset($part['quantity'])) {
                        $sparePart->quantity += $part['quantity']; // Add back to stock
                        $sparePart->save();
                    }
                }
            }
            if ($activity->spare_parts_returned && is_array($activity->spare_parts_returned)) {
                foreach ($activity->spare_parts_returned as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart && isset($part['quantity'])) {
                        $sparePart->quantity -= $part['quantity']; // Remove from stock
                        $sparePart->save();
                    }
                }
            }

            // Apply new spare part movements
            // The model's casts will handle JSON encoding/decoding for storage
            if (isset($validated['spare_parts_used']) && is_array($validated['spare_parts_used'])) {
                foreach ($validated['spare_parts_used'] as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart) {
                        $sparePart->quantity -= $part['quantity'];
                        $sparePart->save();
                    }
                }
            }

            if (isset($validated['spare_parts_returned']) && is_array($validated['spare_parts_returned'])) {
                foreach ($validated['spare_parts_returned'] as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart) {
                        $sparePart->quantity += $part['quantity'];
                        $sparePart->save();
                    }
                }
            }

            $activity->update($validated);

            // Mettre à jour ou créer les réponses aux instructions
            if (isset($validated['instruction_answers'])) {
                foreach ($validated['instruction_answers'] as $instructionId => $value) {
                    InstructionAnswer::updateOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'task_instruction_id' => $instructionId,
                        ],
                        ['value' => $value, 'user_id' => Auth::id()]
                    );
                }
            }
            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité mise à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'activité: ' . $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        DB::beginTransaction();
        try {
            // Revert spare part movements before deleting the activity
            if ($activity->spare_parts_used && is_array($activity->spare_parts_used)) {
                foreach ($activity->spare_parts_used as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart && isset($part['quantity'])) {
                        $sparePart->quantity += $part['quantity']; // Add back to stock
                        $sparePart->save();
                    }
                }
            }
            if ($activity->spare_parts_returned && is_array($activity->spare_parts_returned)) {
                foreach ($activity->spare_parts_returned as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']);
                    if ($sparePart && isset($part['quantity'])) {
                        $sparePart->quantity -= $part['quantity']; // Remove from stock
                        $sparePart->save();
                    }
                }
            }

            $activity->delete();
            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de l\'activité: ' . $e->getMessage());
        }
    }
}
