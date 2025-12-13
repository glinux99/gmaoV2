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
use App\Models\ServiceOrder;
use App\Models\SparePartActivity;
use App\Models\Maintenance;
use App\Models\Expenses;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        $query->with(['task.instructions', 'activityInstructions', 'instructionAnswers', 'task.serviceOrders', 'maintenance']);

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
            'task_id' => 'nullable|exists:tasks,id',
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
            // --- HARMONISATION DU STATUT ICI ---
            'status' => 'nullable|string|in:in_progress,completed,suspended,canceled,scheduled,completed_with_issues,to_be_reviewed_later,awaiting_resources',
            // ------------------------------------
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'instructions' => 'nullable|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            // Validation pour les réponses aux instructions
            'instruction_answers' => 'nullable|array',
            'instruction_answers.*' => 'nullable|string|max:255', // Permet des valeurs nulles, la validation requise se fait au front-end

            // Champs pour ServiceOrder
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
            'maintenance_id' => 'nullable|exists:maintenances,id', // Added for maintenance association

        ]);

        DB::beginTransaction();
        try {
            $validated['user_id'] = $validated['user_id'] ?? Auth::id();

            // Ensure either task_id or maintenance_id is present
            if (empty($validated['task_id']) && empty($validated['maintenance_id'])) {
                throw new \Exception('An activity must be associated with either a task or a maintenance.');
            }

            // Convert spare_parts_used and spare_parts_returned arrays to JSON strings for storage
            $validated['spare_parts_used'] = is_array($validated['spare_parts_used'] ?? null) ? json_encode($validated['spare_parts_used']) : $validated['spare_parts_used'];
            $validated['spare_parts_returned'] = is_array($validated['spare_parts_returned'] ?? null) ? json_encode($validated['spare_parts_returned']) : $validated['spare_parts_returned'];

            $activity = Activity::create($validated);

            // Créer une ServiceOrder si un coût est fourni
            if (isset($validated['service_order_cost']) && $validated['service_order_cost'] > 0) {
                $serviceOrder = ServiceOrder::create([
                    'task_id' => $activity->task_id,
                    'description' => $validated['service_order_description'] ?? 'Prestation liée à l\'activité #' . $activity->id,
                    'cost' => $validated['service_order_cost'],
                    'maintenance_id' => $activity->maintenance_id, // Associate with maintenance if present
                    'status' => 'completed', // On suppose que l'activité étant faite, la prestation l'est aussi
                    'order_date' => now(),
                    'actual_completion_date' => now(),
                ]);

                // Créer une dépense pour la commande de service
                $activity->expenses()->create([
                    'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                    'amount' => $serviceOrder->cost,
                    'expense_date' => now(),
                    'category' => 'external_service', // Ou une autre catégorie appropriée
                    'user_id' => Auth::id(), // L'utilisateur qui a enregistré l'activité
                    'notes' => 'Dépense générée automatiquement pour la prestation de service.',
                    'status' => 'pending', // Ou 'approved' si l'approbation est automatique
                ]);
            }

            // Logic for updating spare part quantities based on 'used' and 'returned'
            // This would involve iterating through the spare_parts_used and spare_parts_returned arrays
            // and updating the quantities in the SparePart model.
            // Example (simplified): Ensure it's an array before iterating
            $sparePartsUsed = json_decode($validated['spare_parts_used'] ?? '[]', true);
            if (is_array($sparePartsUsed)) {
                foreach ($validated['spare_parts_used'] as $sparePartData) {
                    // Créer une dépense pour chaque pièce utilisée
                    $activity->expenses()->create([
                        'description' => 'Pièce détachée utilisée: ' . (\App\Models\SparePart::find($sparePartData['id'])->reference ?? 'N/A'),
                        'amount' => (\App\Models\SparePart::find($sparePartData['id'])->price ?? 0) * $sparePartData['quantity'],
                        'expense_date' => now(),
                        'category' => 'parts',
                        'user_id' => Auth::id(),
                        'notes' => 'Dépense générée automatiquement pour la pièce détachée utilisée.',
                        'status' => 'pending',
                    ]);
                    $sparePart = \App\Models\SparePart::find($sparePartData['id']);
                    if ($sparePart) {
                        SparePartActivity::create([
                            'activity_id' => $activity->id,
                            'spare_part_id' => $sparePart->id,
                            'type' => 'used',
                            'quantity_used' => $sparePartData['quantity'],
                        ]);
                        $sparePart->quantity -= $sparePartData['quantity'];
                        $sparePart->save();
                    }
                }
            }

            $sparePartsReturned = json_decode($validated['spare_parts_returned'] ?? '[]', true);
            if (is_array($sparePartsReturned)) {
                foreach ($sparePartsReturned as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']); // Assuming 'id' is the spare part ID
                    if ($sparePart) {
                        // Enregistrer le mouvement dans la table pivot
                        SparePartActivity::create([
                            'activity_id' => $activity->id,
                            'spare_part_id' => $sparePart->id,
                            'type' => 'returned',
                            'quantity_used' => $part['quantity'], // quantity_used peut être utilisé pour les retours aussi
                        ]);
                        $sparePart->quantity += $part['quantity']; // Ajouter au stock
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
                            'task_instruction_id' => $activity->task_id ? $instructionId : null,
                            'activity_instruction_id'=> $activity->maintenace_id? $instructionId:null
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

        // Determine if the activity is associated with a task or a maintenance
        $isTaskActivity = !is_null($activity->task_id);
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
            // --- HARMONISATION DU STATUT ICI (remplace les termes français) ---
            'status' => 'nullable|string|in:in_progress,completed,suspended,canceled,scheduled,completed_with_issues,to_be_reviewed_later,awaiting_resources',
            // -------------------------------------------------------------------
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'instructions' => 'nullable|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            'instruction_answers' => 'nullable|array',
            'instruction_answers.*' => 'nullable|string|max:255',
            // Champs pour ServiceOrder
            'service_order_cost' => 'nullable|numeric|min:0',
            'maintenance_id' => 'nullable|exists:maintenances,id', // Added for maintenance association
            'service_order_description' => 'nullable|string|required_with:service_order_cost',

        ]);

        DB::beginTransaction();
        try {
            // Revert previous spare part movements
            foreach ($activity->sparePartActivities()->get() as $sparePartActivity) {
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
            // Supprimer tous les enregistrements de la table pivot pour cette activité
            $activity->sparePartActivities()->delete();

            // Supprimer les dépenses associées aux pièces détachées pour cette activité
            $activity->expenses()->where('category', 'parts')->delete(); // Delete only 'parts' category expenses

            // Delete existing ServiceOrders associated with this activity's task or maintenance
            if ($isTaskActivity) {
                ServiceOrder::where('task_id', $activity->task_id)->delete();
            } else {
                ServiceOrder::where('maintenance_id', $activity->maintenance_id)->delete();
            }

            // Delete all expenses related to this activity (excluding parts, which were handled above)
            $activity->expenses()->where('category', '!=', 'parts')->delete();

            // Update or create ServiceOrder
            $serviceOrder = new ServiceOrder(); // Initialize a new ServiceOrder object
            if ($isTaskActivity) {
                $serviceOrder->task_id = $activity->task_id;
            } else {
                $serviceOrder->maintenance_id = $activity->maintenance_id;
            }

            if (isset($validated['service_order_cost']) && $validated['service_order_cost'] > 0) {
                $serviceOrderData = [
                    'description' => $validated['service_order_description'] ?? 'Prestation liée à l\'activité #' . $activity->id,
                    'cost' => $validated['service_order_cost'],
                    'status' => 'completed',
                ];
                $serviceOrder->fill($serviceOrderData);
                $serviceOrder->save();

                // Créer la dépense associée
                $activity->expenses()->create([
                    'description' => 'Coût de la prestation: ' . $serviceOrder->description,
                    'amount' => $serviceOrder->cost,
                    'category' => 'external_service',
                    'user_id' => Auth::id(),
                    'notes' => 'Dépense générée automatiquement pour la prestation de service.',
                    'status' => 'pending',
                    'expense_date' => now(),
                ]);
            }

            // Apply new spare part movements
            // Decode JSON strings back to arrays for processing
            $sparePartsUsed = $validated['spare_parts_used'] ?? [];
            $sparePartsReturned = $validated['spare_parts_returned'] ?? [];
            // The model's casts will handle JSON encoding/decoding for storage
            if (isset($validated['spare_parts_used']) && is_array($validated['spare_parts_used'])) {
                foreach ($validated['spare_parts_used'] as $sparePartData) {
                    $sparePart = \App\Models\SparePart::find($sparePartData['id']);
                    if ($sparePart) {
                        // Créer une dépense pour chaque pièce utilisée
                        $activity->expenses()->create([
                            'description' => 'Pièce détachée utilisée: ' . $sparePart->reference,
                            'amount' => ($sparePart->price ?? 0) * $sparePartData['quantity'],
                            'expense_date' => now(),
                            'category' => 'parts',
                            'user_id' => Auth::id(),
                            'notes' => 'Dépense générée automatiquement pour la pièce détachée utilisée.',
                            'status' => 'pending',
                        ]);
                        // Enregistrer le mouvement dans la table pivot
                        SparePartActivity::create([
                            'activity_id' => $activity->id,
                            'spare_part_id' => $sparePart->id,
                            'type' => 'used',
                            'quantity_used' => $sparePartData['quantity'],
                        ]);
                        $sparePart->quantity -= $sparePartData['quantity'];
                        $sparePart->save();
                    }
                }
            }

            if (is_array($sparePartsReturned)) {
                foreach ($sparePartsReturned as $part) {
                    $sparePart = \App\Models\SparePart::find($part['id']); // Assuming 'id' is the spare part ID
                    if ($sparePart) {
                        // Enregistrer le mouvement dans la table pivot
                        SparePartActivity::create([
                            'activity_id' => $activity->id,
                            'spare_part_id' => $sparePart->id,
                            'type' => 'returned',
                            'quantity_used' => $part['quantity'], // quantity_used peut être utilisé pour les retours aussi
                        ]);
                        $sparePart->quantity += $part['quantity']; // Ajouter au stock
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
                            'activity_id' => $activity->id, // Always associate with the current activity
                            'task_instruction_id' => $isTaskActivity ? $instructionId : null,
                            'activity_instruction_id' => !$isTaskActivity ? $instructionId : null,
                        ],
                        ['value' => $value, 'user_id' => Auth::id()]
                    );
                }
            }
            DB::commit();
            return redirect()->route('activities.index')->with('success', 'Activité mise à jour avec succès.');
        } catch (\Exception $e) {
            return $e;
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
