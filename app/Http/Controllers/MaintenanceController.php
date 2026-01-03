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
use App\Models\Activity;
use Inertia\Inertia;
use App\Models\MaintenanceInstruction;
use App\Models\InstructionTemplate;
use App\Models\Network;
use App\Models\SparePart;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Affiche la liste des maintenances.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->addDay()->toDateString());
        $maintenancesQuery = Maintenance::with(['assignable', 'equipments', 'instructions.equipment', 'networkNode', 'region','instructions'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('scheduled_start_date', [Carbon::parse($startDate)->subDay(), Carbon::parse($endDate)->addDay()]);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        // Transformer l'arbre d'équipements pour le TreeSelect
        $equipmentTree = Equipment::whereNull('parent_id')->with('children.children')->get();
        $transformedEquipmentTree = $this->transformForTreeSelect($equipmentTree);
            //  $maintenancesQuery=( Maintenance::with(['assignable', 'equipments', 'instructions.equipment', 'networkNode'])->latest()
            // ->paginate(10));
        return Inertia::render('Tasks/Maintenances', [
            'maintenances' => $maintenancesQuery,
            'filters' => $request->only('search'),
            'equipments' => Equipment::all(), // Assumant que les équipements sont disponibles pour lier les activités
            'users' => User::all(), // Assumant que les utilisateurs sont disponibles pour lier les activités
            'teams' => Team::all(), // Assumant que les équipes sont disponibles pour lier les activités
            'regions' => Region::with('zones')->get(), //, // Assumant que les régions sont disponibles pour lier les activités
            'tasks' => [], // Assumant que les tâches sont disponibles pour lier les activités
            'spareParts' => SparePart::all(), // Requis pour la sélection de pièces
            'networks' => Network::with('nodes', 'region')->get(), // Ajouté pour la sélection des réseaux
            'equipmentTree' => $transformedEquipmentTree,
            'instructionTemplates' => InstructionTemplate::all(), // Charger les modèles
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
     * Génère les dates de récurrence pour une maintenance.
     */
private function generateRecurrenceDates(array $data): ?array
{
    $recurrenceType = $data['recurrence_type'] ?? null;
    $startDate = isset($data['scheduled_start_date']) ? Carbon::parse($data['scheduled_start_date']) : null;
    $endDate = isset($data['scheduled_end_date']) ? Carbon::parse($data['scheduled_end_date']) : null;

    if (!$recurrenceType || !$startDate || !$endDate || $startDate->gt($endDate)) {
        return null;
    }

    $dates = [];
    $currentDate = $startDate->copy();
    $interval = $data['recurrence_interval'] ?? 1;

    // Utilisation d'une limite de sécurité pour éviter les boucles infinies (ex: 500 occurrences)
    $maxOccurrences = 500;

    while ($currentDate->lte($endDate) && count($dates) < $maxOccurrences) {
        switch ($recurrenceType) {
            case 'daily':
                $dates[] = $currentDate->copy();
                $currentDate->addDays($interval);
                break;

            case 'weekly':
                $daysOfWeek = $data['recurrence_days'] ?? []; // Ex: [1, 3] pour Lundi, Mercredi
                if (empty($daysOfWeek) || in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                    $dates[] = $currentDate->copy();
                }
                $currentDate->addDay();
                break;

            case 'monthly':
            case 'quarterly':
            case 'biannual':
            case 'annual':
                // Si on cherche un jour spécifique du mois (ex: le 15 du mois)
                $dayOfMonth = $data['recurrence_day_of_month'] ?? $startDate->day;

                // On s'assure que la date courante est bien fixée au jour voulu du mois
                $currentDate->day($dayOfMonth);

                // On vérifie si après ajustement on est toujours dans l'intervalle
                if ($currentDate->gte($startDate) && $currentDate->lte($endDate)) {
                    $dates[] = $currentDate->copy();
                }

                // Incrémentation selon le type
                if ($recurrenceType === 'monthly') $currentDate->addMonths($interval);
                elseif ($recurrenceType === 'quarterly') $currentDate->addMonths(3);
                elseif ($recurrenceType === 'biannual') $currentDate->addMonths(6);
                elseif ($recurrenceType === 'annual') $currentDate->addYears($interval);
                break;

            default:
                return $dates;
        }
    }

    return $dates;
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
        'network_node_id' => 'nullable|exists:network_nodes,id',
        'network_id' => 'nullable|exists:networks,id',
        // Validation corrigée: permet des entiers ou des chaînes pour les IDs
        'equipment_ids' => 'nullable|array',
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

return $validator->messages();

        // Log::error('Erreur de validation:', $validator->errors()->toArray()); // Décommenter pour debug
        return redirect()->back()->withErrors($validator)->withInput();
    }

    DB::beginTransaction();
    try {
        $validatedData = $validator->validated();

        // Générer les dates de récurrence et les ajouter aux données validées
        $regeneratedDates = $this->generateRecurrenceDates($validatedData);
        $validatedData['regenerated_dates'] = $regeneratedDates ? json_encode($regeneratedDates) : null;

        // Création de l'enregistrement principal
        $maintenance = Maintenance::create($validatedData);

        // Créer une activité correspondante
        $activity = Activity::create([
            'maintenance_id' => $maintenance->id,
            'title' => 'Activité pour: ' . $maintenance->title,
            'assignable_type' => $maintenance->assignable_type,
            'assignable_id' => $maintenance->assignable_id,
            'status' => 'scheduled', // Statut par défaut
            'actual_start_time' => $maintenance->scheduled_start_date,
            'actual_end_time' => $maintenance->scheduled_end_date,
            'priority' => $maintenance->priority,
            'user_id' => Auth::id(),
        ]);

        // Attacher les équipements à l'activité
        if (!empty($validatedData['equipment_ids'])) {
            $activity->equipment()->attach($validatedData['equipment_ids']);
        }


        // Attacher les équipements (relation Many-to-Many via table pivot)
        if (!empty($validatedData['equipment_ids'])) {
                $networkNodeId = $validator->validated()['network_node_id'];

            // Mettre à jour les équipements liés (synchronisation Many-to-Many)
            $syncData = collect($validator->validated()['equipment_ids'])->mapWithKeys(function ($id) use ($networkNodeId) {
    return [(int) $id => [
        'network_node_id' => $networkNodeId
    ]];
})->toArray();
    $maintenance->equipments()->detach(); // Détacher les équipements existants
// 3. On attache avec les données du pivot
$maintenance->equipments()->attach($syncData);
        }

        // NOUVEAU : Mettre à jour la date de prochaine maintenance sur le NetworkNode
        if (!empty($validatedData['network_node_id']) && !empty($validatedData['scheduled_start_date'])) {
            $networkNode = \App\Models\NetworkNode::find($validatedData['network_node_id']);
            if ($networkNode) {
                $networkNode->next_maintenance_date = $validatedData['scheduled_start_date'];
                $networkNode->save();
            }
        }

        // 2. Enregistrer les instructions (relation HasMany)
        if (isset($validatedData['node_instructions'])) {
            foreach ($validatedData['node_instructions'] as $equipmentId => $instructions) {
                // S'assurer que equipmentId est un entier pour l'enregistrement
                $cleanEquipmentId = (int) $equipmentId;
                foreach ($instructions as $instructionData) {
                    // Crée l'instruction et l'associe à la maintenance et à l'équipement
                    $maintenanceInstruction = $maintenance->instructions()->create(array_merge($instructionData, ['equipment_id' => $cleanEquipmentId]));

                    // Créer l'instruction correspondante pour l'activité
                    $activity->activityInstructions()->create([
                        'label' => $maintenanceInstruction->label,
                        'type' => $maintenanceInstruction->type,
                        'is_required' => $maintenanceInstruction->is_required,
                    ]);
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
            'network_node_id' => 'nullable|exists:network_nodes,id',
             'network_id' => 'nullable|exists:networks,id',
            'equipment_ids' => 'nullable|array',
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

            $validatedData = $validator->validated();
            // Générer les dates de récurrence et les ajouter aux données validées
            $regeneratedDates = $this->generateRecurrenceDates($validatedData);
            $validatedData['regenerated_dates'] = $regeneratedDates ? json_encode($regeneratedDates) : null;

            $maintenance->update($validatedData);

            // Mettre à jour ou créer l'activité correspondante
            $activity = Activity::updateOrCreate(
                ['maintenance_id' => $maintenance->id],
                [
                    'title' => 'Activité pour: ' . $maintenance->title,
                    'assignable_type' => $maintenance->assignable_type,
                    'assignable_id' => $maintenance->assignable_id,
                    'status' => $maintenance->status === 'completed' ? 'completed' : 'scheduled',
                    'actual_start_time' => $maintenance->scheduled_start_date,
                    'actual_end_time' => $maintenance->scheduled_end_date,
                    'priority' => $maintenance->priority,
                    'user_id' => Auth::id(),
                    'problem_resolution_description' => $maintenance->description,
                    'proposals' => null,
                    'additional_information' => null,
                ]
            );

            // Mettre à jour les équipements liés (synchronisation Many-to-Many)
            if (isset($validator->validated()['equipment_ids'])) {
                $networkNodeId = $validator->validated()['network_node_id'];

            // Mettre à jour les équipements liés (synchronisation Many-to-Many)
            $syncData = collect($validator->validated()['equipment_ids'])->mapWithKeys(function ($id) use ($networkNodeId) {
    return [(int) $id => [
        'network_node_id' => $networkNodeId
    ]];
})->toArray();
    $maintenance->equipments()->detach(); // Détacher les équipements existants
// 3. On attache avec les données du pivot
$maintenance->equipments()->attach($syncData);

                // NOUVEAU : Mettre à jour la date de prochaine maintenance sur le NetworkNode
                $validatedData = $validator->validated();
                $oldNodeId = $maintenance->getOriginal('network_node_id');
                $newNodeId = $validatedData['network_node_id'] ?? null;

                // Si le noeud a changé, on efface la date sur l'ancien noeud
                if ($oldNodeId && $oldNodeId != $newNodeId) {
                    \App\Models\NetworkNode::where('id', $oldNodeId)->update(['next_maintenance_date' => null]);
                }

                // On met à jour le nouveau noeud
                if ($newNodeId && !empty($validatedData['scheduled_start_date'])) {
                    \App\Models\NetworkNode::where('id', $newNodeId)->update([
                        'next_maintenance_date' => $validatedData['scheduled_start_date']
                    ]);
                }

                // Mettre à jour les équipements de l'activité
                $activity->equipment()->sync($validator->validated()['equipment_ids']);
            }


            // Mettre à jour les instructions
            $maintenance->instructions()->delete(); // Supprimer les anciennes instructions
            $activity->activityInstructions()->delete(); // Supprimer les anciennes instructions de l'activité

            if ($request->has('node_instructions')) {
                foreach ($request->input('node_instructions') as $equipmentId => $instructions) {
                    foreach ($instructions as $instructionData) {
                        $maintenanceInstruction = $maintenance->instructions()->create([
                            'equipment_id' => $equipmentId,
                            'label' => $instructionData['label'],
                            'type' => $instructionData['type'],
                            'is_required' => $instructionData['is_required'],
                        ]);
                        // Créer l'instruction correspondante pour l'activité
                        $activity->activityInstructions()->create([
                            'label' => $maintenanceInstruction->label,
                            'type' => $maintenanceInstruction->type,
                            'is_required' => $maintenanceInstruction->is_required,
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
            return $e;
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
            // $maintenance->expenses()->delete(); // Supprimer les dépenses associées
            $maintenance->equipments()->detach(); // Détacher les équipements
            $maintenance->instructions()->delete(); // Supprimer les instructions
            $maintenance->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $e;
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de la maintenance: ' . $e->getMessage());
        }
        return redirect()->route('maintenances.index')->with('success', 'Maintenance supprimée avec succès.');
    }
}
