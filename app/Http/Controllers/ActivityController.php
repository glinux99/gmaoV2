<?php

namespace App\Http\Controllers;

use App\Models\SparePartTask;
use Inertia\Inertia;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\InstructionAnswer;
use App\Models\ActivityInstruction;
use App\Models\SparePart;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceOrder;
use App\Models\SparePartActivity;
use App\Models\Maintenance;
use App\Models\Expenses;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = request();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Activity::query()
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('actual_start_time', [$startDate, $endDate]);
            });

        if (request()->has('search')) {
            $search = request('search');
            $query->where('problem_resolution_description', 'like', '%' . $search . '%')
                ->orWhere('proposals', 'like', '%' . $search . '%')
                ->orWhere('instructions', 'like', '%' . $search . '%')
                ->orWhereHas('task', fn ($q) => $q->where('title', 'like', '%' . $search . '%'))
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        // Eager load all necessary relationships for display and edit
        $query->with(['task.instructions', 'activityInstructions', 'instructionAnswers', 'task.serviceOrders', 'maintenance', 'sparePartActivities.sparePart']);

        return Inertia::render('Tasks/MyActivities', [
            'activities' => $query->paginate(100),
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
        'task_id' => 'nullable|exists:tasks,id',
        'user_id' => 'nullable|exists:users,id',
        'actual_start_time' => 'nullable|date',
        'actual_end_time' => 'nullable|date|after_or_equal:actual_start_time',
        'parent_id' => 'nullable|exists:activities,id',
        'assignable_type' => ['nullable', 'string'], // Rule::in simplifié pour l'exemple
        'assignable_id' => 'nullable|integer',
        'jobber' => 'nullable|string',
        'spare_parts_used' => 'nullable|array',
        'spare_parts_used.*.id' => 'required_with:spare_parts_used|exists:spare_parts,id',
        'spare_parts_used.*.quantity' => 'required_with:spare_parts_used|integer|min:1',
        'spare_parts_returned' => 'nullable|array',
        'spare_parts_returned.*.id' => 'required_with:spare_parts_returned|exists:spare_parts,id',
        'spare_parts_returned.*.quantity' => 'required_with:spare_parts_returned|integer|min:1',
        'status' => 'nullable|string|in:in_progress,completed,suspended,canceled,scheduled,completed_with_issues,to_be_reviewed_later,awaiting_resources,en cours,terminée,suspendue,annulée,planifiée',
        'problem_resolution_description' => 'nullable|string|max:65535',
        'proposals' => 'nullable|string|max:65535',
        'additional_information' => 'nullable|string|max:65535',
        'instructions' => 'nullable|array',
        'instructions.*.id' => 'required',
        'instructions.*.label' => 'required|string',
        'instruction_answers' => 'nullable|array',
        'instruction_answers.*' => 'nullable|string|max:255',
        'service_order_cost' => 'nullable|numeric|min:0',
        'service_order_description' => 'nullable|string|required_with:service_order_cost',
        'maintenance_id' => 'nullable|exists:maintenances,id',
    ]);

    DB::beginTransaction();
    try {
        $validated['user_id'] = $validated['user_id'] ?? Auth::id();

        if (empty($validated['task_id']) && empty($validated['maintenance_id'])) {
            throw new \Exception('Une activité doit être associée à une tâche ou une maintenance.');
        }

        // 1. Création de l'activité (on exclut les champs qui ne sont pas dans la table activities)
        $activityData = Arr::except($validated, [
            'instructions',
            'instruction_answers',
            'service_order_cost',
            'service_order_description',
            'spare_parts_used',
            'spare_parts_returned',
            'equipment_ids'
        ]);

      $activity = Activity::create($activityData);

        // 2. GESTION DU SERVICE ORDER
        if (isset($validated['service_order_cost']) && $validated['service_order_cost'] > 0) {
            $serviceOrder = ServiceOrder::create([
                'task_id' => $activity->task_id,
                'maintenance_id' => $activity->maintenance_id,
                'description' => $validated['service_order_description'] ?? 'Prestation liée à l\'activité #' . $activity->id,
                'cost' => $validated['service_order_cost'],
                'status' => 'completed',
                'order_date' => now(),
            ]);

            $activity->expenses()->create([
                'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                'amount' => $serviceOrder->cost,
                'expense_date' => now(),
                'category' => 'external_service',
                'user_id' => Auth::id(),
                'status' => 'pending',
            ]);
        }

        // 3. GESTION DES PIÈCES UTILISÉES
        $sparePartsUsed = $request->input('spare_parts_used', []);
        foreach ($sparePartsUsed as $sparePartData) {
            $sparePart = \App\Models\SparePart::find($sparePartData['id']);
            if ($sparePart) {
                $activity->expenses()->create([
                    'description' => 'Pièce utilisée: ' . $sparePart->reference,
                    'amount' => ($sparePart->price ?? 0) * $sparePartData['quantity'],
                    'expense_date' => now(),
                    'category' => 'parts',
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                ]);

                SparePartActivity::create([
                    'activity_id' => $activity->id,
                    'spare_part_id' => $sparePart->id,
                    'type' => 'used',
                    'quantity_used' => $sparePartData['quantity'],
                ]);

                $sparePart->decrement('quantity', $sparePartData['quantity']);
            }
        }

        // 4. GESTION DES PIÈCES RETOURNÉES
        $sparePartsReturned = $request->input('spare_parts_returned', []);
        foreach ($sparePartsReturned as $part) {
            $sparePart = \App\Models\SparePart::find($part['id']);
            if ($sparePart) {
                SparePartActivity::create([
                    'activity_id' => $activity->id,
                    'spare_part_id' => $sparePart->id,
                    'type' => 'returned',
                    'quantity_used' => $part['quantity'],
                ]);
                $sparePart->increment('quantity', $part['quantity']);
            }
        }

        // 5. GESTION DES INSTRUCTIONS (Questions + Réponses)
        if ($request->has('instructions')) {
            $instructionIdMap = [];

            foreach ($request->instructions as $instrData) {
                $tempId = $instrData['id'];

                if (is_string($tempId) && str_starts_with($tempId, 'new_')) {
                    $newInstruction = $activity->activityInstructions()->create([
                        'label' => $instrData['label'],
                        'type' => $instrData['type'] ?? 'text',
                        'is_required' => $instrData['is_required'] ?? false,
                    ]);
                    $instructionIdMap[$tempId] = $newInstruction->id;
                } else {
                    $instructionIdMap[$tempId] = $tempId;
                }
            }

            if ($request->has('instruction_answers')) {
                foreach ($request->instruction_answers as $tempId => $value) {
                    $realId = $instructionIdMap[$tempId] ?? null;
                    if ($realId) {
                        InstructionAnswer::create([
                            'activity_id' => $activity->id,
                            'activity_instruction_id' => $realId,
                            'value' => $value,
                            'user_id' => Auth::id()
                        ]);
                    }
                }
            }
        }

        DB::commit();
        return redirect()->route('activities.index')->with('success', 'Activité créée avec succès.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
    }
}

    /**
     * Store multiple new activities.
     */
public function bulkStore(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            // Validation au niveau racine
            'maintenance_id' => 'required|exists:maintenances,id',

            // Validation du tableau d'activités
            'activities' => 'required|array',
            'activities.*' => 'required|array',

            // Nouveaux champs dans vos données
            'activities.*.title' => 'required|string|max:255',
            'activities.*.equipment_ids' => 'nullable|array',
            'activities.*.equipment_ids.*' => 'integer|exists:equipment,id',

            // Champs d'activité standard
            'activities.*.task_id' => 'nullable|exists:tasks,id',
            'activities.*.user_id' => 'nullable|exists:users,id',
            'activities.*.actual_start_time' => 'nullable|date',
            'activities.*.actual_end_time' => 'nullable|date|after_or_equal:activities.*.actual_start_time',
            'activities.*.parent_id' => 'nullable|exists:activities,id',
            'activities.*.jobber' => 'nullable|integer|min:1',
            // --- HARMONISATION DU STATUT ICI ---
            'activities.*.status' => 'nullable|string|in:in_progress,completed,suspended,canceled,scheduled,completed_with_issues,to_be_reviewed_later,awaiting_resources',
            // ------------------------------------
            'activities.*.problem_resolution_description' => 'nullable|string',
            'activities.*.proposals' => 'nullable|string',
            'activities.*.additional_information' => 'nullable|string|max:65535',

            // Instructions brutes envoyées dans vos données (si elles doivent être stockées)
            'activities.*.instructions' => 'nullable|array',
            'activities.*.instructions.*.label' => 'required|string|max:255',
            'activities.*.instructions.*.type' => 'required|string|max:50',
            'activities.*.instructions.*.is_required' => 'boolean',

            // Champs liés aux services (conservés pour complétude)
            'activities.*.service_order_cost' => 'nullable|numeric|min:0',
            'activities.*.service_order_description' => 'nullable|string|max:65535|required_with:activities.*.service_order_cost',

            // Gestion des pièces détachées utilisées (Nom de champ ajusté à 'spare_parts')
            'activities.*.spare_parts' => 'nullable|array',
            'activities.*.spare_parts.*.id' => 'required|exists:spare_parts,id',
            'activities.*.spare_parts.*.quantity_used' => 'required|integer|min:1',

            // Gestion des pièces détachées retournées (conservé)
            'activities.*.spare_parts_returned' => 'nullable|array',
            'activities.*.spare_parts_returned.*.id' => 'required|exists:spare_parts,id',
            'activities.*.spare_parts_returned.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            Log::error('Erreur de validation lors du bulkStore:', $validator->errors()->toArray());
            return response()->json([
                   'message' => 'Erreur de validation des activités. Veuillez vérifier les données envoyées.',
                   'errors' => $validator->errors()
                 ], 422);
        }

        $validatedData = $validator->validated();
        $globalMaintenanceId = $validatedData['maintenance_id'];

        // 2. Traitement des données et Transaction
        DB::beginTransaction();
        try {
            foreach ($validatedData['activities'] as $activityData) {
                $activityData['user_id'] = $activityData['user_id'] ?? Auth::id();

                // Assurer l'association Maintenance si elle vient du niveau racine
                if (!isset($activityData['task_id']) && $globalMaintenanceId) {
                       $activityData['maintenance_id'] = $globalMaintenanceId;
                }

                // Déterminer l'entité polyomorphe (expensable)
                if (isset($activityData['task_id'])) {
                    $activityData['expensable_type'] = 'App\\Models\\Task';
                    $activityData['expensable_id'] = $activityData['task_id'];
                } elseif (isset($activityData['maintenance_id'])) {
                    $activityData['expensable_type'] = 'App\\Models\\Maintenance';
                    $activityData['expensable_id'] = $activityData['maintenance_id'];
                }

                // Filtrage des données (retirer les tableaux d'associations pour la création de l'Activity)
                $activityCreationData = array_diff_key($activityData, array_flip([
                    'spare_parts',              // Le nouveau nom
                    'spare_parts_returned',
                    'instruction_answers',
                    'service_order_cost',
                    'service_order_description',
                    'equipment_ids',            // Association Many-to-Many
                    'instructions',             // Nouvelles instructions
                ]));

                $activity = Activity::create($activityCreationData);

                // --- GESTION DES RELATIONS MULTIPLES ---

                // 3. Gérer les Relations Equipment (Many-to-Many)
                if (isset($activityData['equipment_ids']) && is_array($activityData['equipment_ids'])) {
                    $activity->equipment()->attach($activityData['equipment_ids']);
                }

                // 4. Gérer les Pièces Détachées Utilisées (Ajusté pour 'spare_parts' et 'quantity_used')
                if (isset($activityData['spare_parts']) && is_array($activityData['spare_parts'])) {
                    foreach ($activityData['spare_parts'] as $sparePartData) {
                        $quantity = $sparePartData['quantity_used'];
                        $sparePart = SparePart::find($sparePartData['id']);

                        if ($sparePart) {
                            $totalCost = ($sparePart->price ?? 0) * $quantity;

                            // Créer l'Expense
                            $activity->expenses()->create([
                                'description' => 'Pièce détachée utilisée: ' . ($sparePart->reference ?? 'N/A') . ' (x' . $quantity . ')',
                                'amount' => $totalCost,
                                'expense_date' => now(),
                                'category' => 'parts',
                                'user_id' => Auth::id(),
                                'notes' => 'Dépense générée automatiquement pour la pièce détachée utilisée.',
                                'status' => 'pending',
                            ]);

                            // Créer l'enregistrement de liaison (pivot)
                            SparePartActivity::create([
                                'activity_id' => $activity->id,
                                'spare_part_id' => $sparePart->id,
                                'type' => 'used',
                                'quantity_used' => $quantity,
                            ]);

                            // Mettre à jour le stock
                            if ($sparePart->quantity >= $quantity) {
                                $sparePart->quantity -= $quantity;
                                $sparePart->save();
                            } else {
                                // Lancer une exception pour annuler la transaction
                                throw new Exception("Stock insuffisant pour la pièce détachée ID: {$sparePart->id}. (Requis: {$quantity}, Disponible: {$sparePart->quantity}).");
                            }
                        }
                    }
                }

                // 5. Gérer les Pièces Détachées Retournées (inchangé)
                if (isset($activityData['spare_parts_returned']) && is_array($activityData['spare_parts_returned'])) {
                    foreach ($activityData['spare_parts_returned'] as $sparePartReturnedData) {
                        $sparePart = SparePart::find($sparePartReturnedData['id']);
                        if ($sparePart) {
                            $quantity = $sparePartReturnedData['quantity'];
                            SparePartActivity::create([
                                'activity_id' => $activity->id,
                                'spare_part_id' => $sparePart->id,
                                'type' => 'returned',
                                'quantity_returned' => $quantity,
                            ]);
                            $sparePart->quantity += $quantity;
                            $sparePart->save();
                        }
                    }
                }

                // 6. Gérer la création des Instructions (si envoyées)
                if (isset($activityData['instructions']) && is_array($activityData['instructions'])) {
                    // Assurez-vous que votre modèle Activity a une relation 'instructions()'
                    // qui pointe vers le bon modèle (ex: ActivityInstruction)
                    foreach ($activityData['instructions'] as $instruction) {
                        $activity->activityInstructions()->create([
                            'label' => $instruction['label'],
                            'type' => $instruction['type'],
                            'is_required' => $instruction['is_required'] ?? false,
                            // Ajoutez ici d'autres champs de TaskInstruction si nécessaire
                        ]);
                    }
                }

                // 7. Gérer la Commande de Service (inchangé)
                if (isset($activityData['service_order_cost']) && $activityData['service_order_cost'] > 0) {
                    $serviceOrder = ServiceOrder::create([
                        'task_id' => $activityData['task_id'] ?? null,
                        'maintenance_id' => $activityData['maintenance_id'] ?? null,
                        'description' => $activityData['service_order_description'] ?? 'Prestation liée à l\'activité #' . $activity->id,
                        'cost' => $activityData['service_order_cost'],
                        'status' => 'completed',
                        'order_date' => now(),
                        'actual_completion_date' => now(),
                    ]);

                    $activity->expenses()->create([
                        'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                        'amount' => $serviceOrder->cost,
                        'expense_date' => now(),
                        'category' => 'external_service',
                        'user_id' => Auth::id(),
                        'notes' => 'Dépense générée automatiquement pour la prestation de service.',
                        'status' => 'pending',
                    ]);
                }
            }

            DB::commit();

            // Succès
            return response()->json([
                'message' => 'Activités créées avec succès.',
                'redirect' => route('activities.index')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            // Afficher l'erreur dans les logs et la retourner en JSON
            Log::error('Erreur lors du bulkStore des activités:', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création des activités.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        return Inertia::render('Tasks/MyActivities/Show', [
            'activity' => $activity->load(['task.serviceOrders', 'user', 'parent', 'children', 'assignable']),
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
            'status' => 'nullable|string|in:in_progress,completed,suspended,canceled,scheduled,completed_with_issues,to_be_reviewed_later,awaiting_resources,en cours,terminée,suspendue,annulée,planifiée',
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            'instructions' => 'nullable|array',
            'instruction_answers' => 'nullable|array',
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
            'maintenance_id' => 'nullable|exists:maintenances,id',
        ]);

        DB::beginTransaction();
        try {
            // --- 1. MISE À JOUR DE L'ACTIVITÉ PRINCIPALE ---
            $activity->update(Arr::except($validated, [
                'instructions', 'instruction_answers', 'spare_parts_used', 'spare_parts_returned',
                'service_order_cost', 'service_order_description'
            ]));

            // --- 2. SYNCHRONISATION DES PIÈCES DÉTACHÉES ---
            $this->syncSpareParts($activity, $validated);

            // --- 3. SYNCHRONISATION DES INSTRUCTIONS ET RÉPONSES ---
            $this->syncInstructionsAndAnswers($activity, $validated);

            // --- 4. GESTION DU SERVICE ORDER ---
            $this->processServiceOrder($activity, $validated);

            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur Update Activity: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Synchronize spare parts for an activity.
     */
    private function syncSpareParts(Activity $activity, array $data)
    {
        // Revert old stock movements
        foreach ($activity->sparePartActivities as $spa) {
            if ($spa->sparePart) {
                if ($spa->type === 'used') {
                    $spa->sparePart->increment('quantity', $spa->quantity_used);
                } elseif ($spa->type === 'returned') {
                    $spa->sparePart->decrement('quantity', $spa->quantity_returned);
                }
            }
        }
        $activity->sparePartActivities()->delete();
        $activity->expenses()->where('category', 'parts')->delete();

        // Apply new movements
        $partsUsed = $data['spare_parts_used'] ?? [];
        foreach ($partsUsed as $partData) {
            $sparePart = SparePart::find($partData['id']);
            if ($sparePart) {
                $activity->sparePartActivities()->create(['spare_part_id' => $sparePart->id, 'type' => 'used', 'quantity_used' => $partData['quantity']]);
                $activity->expenses()->create(['description' => 'Pièce utilisée: ' . $sparePart->reference, 'amount' => ($sparePart->price ?? 0) * $partData['quantity'], 'category' => 'parts', 'user_id' => Auth::id(), 'expense_date' => now(), 'status' => 'pending']);
                $sparePart->decrement('quantity', $partData['quantity']);
            }
        }

        $partsReturned = $data['spare_parts_returned'] ?? [];
        foreach ($partsReturned as $partData) {
            $sparePart = SparePart::find($partData['id']);
            if ($sparePart) {
                $activity->sparePartActivities()->create(['spare_part_id' => $sparePart->id, 'type' => 'returned', 'quantity_returned' => $partData['quantity']]);
                $sparePart->increment('quantity', $partData['quantity']);
            }
        }
    }

    /**
     * Synchronize instructions and their answers.
     */
    private function syncInstructionsAndAnswers(Activity $activity, array $data)
    {
        $instructionIdMap = [];
        $incomingInstructionIds = [];

        // Create or update instructions
        foreach ($data['instructions'] ?? [] as $instrData) {
            $tempId = $instrData['id'];
            if (is_string($tempId) && str_starts_with($tempId, 'new_')) {
                $newInstr = $activity->activityInstructions()->create(Arr::only($instrData, ['label', 'type', 'is_required']));
                $instructionIdMap[$tempId] = $newInstr->id;
                $incomingInstructionIds[] = $newInstr->id;
            } else {
                $activity->activityInstructions()->where('id', $tempId)->update(Arr::only($instrData, ['label']));
                $instructionIdMap[$tempId] = $tempId;
                $incomingInstructionIds[] = (int)$tempId;
            }
        }

        // Delete instructions that are no longer present
        $activity->activityInstructions()->whereNotIn('id', $incomingInstructionIds)->delete();

        // Update or create answers
        foreach ($data['instruction_answers'] ?? [] as $tempId => $value) {
            $realId = $instructionIdMap[$tempId] ?? $tempId;
            if (in_array($realId, $incomingInstructionIds)) {
                InstructionAnswer::updateOrCreate(
                    ['activity_id' => $activity->id, 'activity_instruction_id' => $realId],
                    ['value' => $value, 'user_id' => Auth::id()]
                );
            }
        }
    }

    /**
     * Process the service order for an activity.
     */
    private function processServiceOrder(Activity $activity, array $data)
    {
        // Clean up old service order and related expense
        $activity->serviceOrder()->delete();
        $activity->expenses()->where('category', 'external_service')->delete();

        if (isset($data['service_order_cost']) && $data['service_order_cost'] > 0) {
            $serviceOrder = ServiceOrder::create([
                'task_id' => $activity->task_id,
                'maintenance_id' => $activity->maintenance_id,
                'description' => $data['service_order_description'] ?? 'Prestation pour activité #' . $activity->id,
                'cost' => $data['service_order_cost'],
                'status' => 'completed',
                'order_date' => now(),
            ]);

            $activity->expenses()->create([
                'description' => 'Coût prestation: ' . $serviceOrder->description,
                'amount' => $serviceOrder->cost,
                'category' => 'external_service',
                'user_id' => Auth::id(),
                'expense_date' => now(),
                'status' => 'pending',
            ]);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        DB::beginTransaction();
        try {
            // Delete all expenses associated with this activity
            $activity->expenses()->delete(); // Supprime toutes les dépenses liées à cette activité

            // Revert spare part movements before deleting the activity
            foreach ($activity->sparePartActivities as $sparePartActivity) {
                $sparePart = \App\Models\SparePart::find($sparePartActivity->spare_part_id);
                if ($sparePart) {
                    if ($sparePartActivity->type === 'used') {
                        $sparePart->quantity += $sparePartActivity->quantity_used; // Add back to stock
                        $sparePart->save();
                    }
                    if ($sparePartActivity->type === 'returned') {
                        $sparePart->quantity -= $sparePartActivity->quantity_used; // Remove from stock
                        $sparePart->save();
                    }
                }
            }
            // Supprimer les enregistrements de la table pivot
            $activity->sparePartActivities()->delete();

            // Supprimer les réponses aux instructions
            $activity->instructionAnswers()->delete();

            // Supprimer l'activité
            $activity->delete();

            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de l\'activité:', ['exception' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de l\'activité: ' . $e->getMessage());
        }
    }
}
