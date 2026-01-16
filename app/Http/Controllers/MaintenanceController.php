<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Team;
use App\Models\User;
use App\Models\Region;
use App\Models\NetworkNode;
use App\Models\ServiceOrder;
use App\Models\Activity;
use App\Models\InstructionTemplate;
use App\Models\Network;
use App\Models\SparePart;
use Illuminate\Http\Request;
use App\Models\MaintenanceStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Affiche la liste des maintenances avec filtres.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $maintenances = Maintenance::with(['assignable', 'equipments', 'instructions.equipment', 'networkNode', 'region', 'statusHistories.user'])
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('scheduled_start_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $equipmentTree = Equipment::whereNull('parent_id')->with('children.children')->get();

        return Inertia::render('Tasks/Maintenances', [
            'maintenances' => $maintenances,
            'filters' => $request->only('search', 'start_date', 'end_date'),
            'equipments' => Equipment::all(),
            'users' => User::all(),
            'teams' => Team::all(),
            'regions' => Region::with('zones')->get(),
            'spareParts' => SparePart::all(),
            'networks' => Network::with('nodes', 'region')->get(),
            'equipmentTree' => $this->transformForTreeSelect($equipmentTree),
            'instructionTemplates' => InstructionTemplate::all(),
        ]);
    }

    /**
     * Enregistre une nouvelle maintenance.
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateMaintenance($request);

        DB::beginTransaction();
        try {
            // 1. Calcul des dates de récurrence
            $regeneratedDates = $this->generateRecurrenceDates($validatedData);
            $validatedData['regenerated_dates'] = $regeneratedDates ? json_encode($regeneratedDates) : null;

            // 2. Création de la Maintenance
            $maintenance = Maintenance::create($validatedData);

            // 3. Création des activités
            $startTime = Carbon::parse($maintenance->scheduled_start_date)->format('H:i:s');
            $endTime = Carbon::parse($maintenance->scheduled_end_date)->format('H:i:s');
            $activities = [];

            if ($regeneratedDates) {
                foreach ($regeneratedDates as $date) {
                    $activityDate = Carbon::parse($date)->startOfDay();

                    // Construction du titre de l'activité
                    $activityTitle = 'Maintenance sur ' . $maintenance->title;
                    if ($maintenance->equipments()->count() > 0) {
                        if ($maintenance->equipments()->count() == 1) {
                            $equipment = $maintenance->equipments()->first();
 $regionName = $maintenance->networkNode->region->designation ?? 'N/A';
 $zoneName = $maintenance->networkNode->network->nomenclature ?? 'N/A';
                            $activityTitle = 'Maintenance sur ' . $equipment->designation . ' (' . $regionName . ' / ' . $zoneName . ')';
                        } else {
                            $regionName = $maintenance->region->designation ?? 'N/A';
                            $activityTitle = 'Maintenance sur ' . $maintenance->equipments()->count() . ' équipements (' . $regionName . ')';
                        }
                    } elseif ($maintenance->title) {
                        $activityTitle = 'Maintenance sur ' . $maintenance->title;
                    }

                    $activities[] = Activity::create([
                        'maintenance_id' => $maintenance->id,
                        'title' => $activityTitle,
                        'assignable_type' => $maintenance->assignable_type,
                        'assignable_id' => $maintenance->assignable_id,
                        'region_id'=>$maintenance->region_id,
                        'status' => 'scheduled',
                        'actual_start_time' => $activityDate->copy()->setTimeFromTimeString($startTime),
                        'actual_end_time' => $activityDate->copy()->setTimeFromTimeString($endTime),
                        'priority' => $maintenance->priority,
                        'user_id' => Auth::id(),
                    ]);
                }
            } else {
                // Comportement par défaut : une seule activité
                $activityTitle = 'Maintenance sur ' . $maintenance->title;
                if ($maintenance->equipments()->count() > 0) {
                    if ($maintenance->equipments()->count() == 1) {
                        $equipment = $maintenance->equipments()->first();

  $regionName = $maintenance->networkNode->region->designation ?? 'N/A';
 $zoneName = $maintenance->networkNode->network->nomenclature ?? 'N/A';

                        $activityTitle = 'Maintenance sur ' . $equipment->designation . ' (' . $regionName . ' / ' . $zoneName . ')';
                    } else {
                        $regionName = $maintenance->region->designation ?? 'N/A';
                        $activityTitle = 'Maintenance sur ' . $maintenance->equipments()->count() . ' équipements (' . $regionName . ')';
                    }
                } elseif ($maintenance->title) {
                    $activityTitle = 'Maintenance sur ' . $maintenance->title;
                }

                $activities[] = Activity::create([
                    'maintenance_id' => $maintenance->id,
                    'title' => $activityTitle,
                    'assignable_type' => $maintenance->assignable_type,
                    'assignable_id' => $maintenance->assignable_id,
                    'status' => 'scheduled',
                    'region_id'=>$maintenance->region_id,
                    'actual_start_time' => $maintenance->scheduled_start_date,
                    'actual_end_time' => $maintenance->scheduled_end_date,
                    'priority' => $maintenance->priority,
                    'user_id' => Auth::id(),
                ]);
            }

            // 4. Gestion des équipements (Pivot avec NetworkNode)
            if (!empty($validatedData['equipment_ids'])) {
                $nodeId = $validatedData['network_node_id'] ?? null;
                $syncData = collect($validatedData['equipment_ids'])->mapWithKeys(function ($id) use ($nodeId) {
                    return [(int) $id => ['network_node_id' => $nodeId]];
                })->toArray();

                $maintenance->equipments()->sync($syncData);
                foreach ($activities as $activity) {
                    $activity->equipment()->sync($validatedData['equipment_ids']);
                }
            }

            // 5. Instructions & Service Order
            foreach ($activities as $activity) {
                $this->processInstructions($maintenance, $activity, $validatedData['node_instructions'] ?? []);
            }
            $this->processServiceOrder($maintenance, $validatedData);

            // 6. Mise à jour de la date sur le NetworkNode
            if (!empty($validatedData['network_node_id'])) {
                NetworkNode::where('id', $validatedData['network_node_id'])
                    ->update(['next_maintenance_date' => $validatedData['scheduled_start_date']]);
            }

            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'Maintenance créée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur Store Maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la création : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Met à jour une maintenance existante.
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        $validatedData = $this->validateMaintenance($request);

        DB::beginTransaction();
        try {
            $oldNodeId = $maintenance->network_node_id;
            $originalRegeneratedDates = $maintenance->regenerated_dates;
            $originalStatus = $maintenance->status;

            // 1. Mise à jour récurrence
            $regeneratedDates = $this->generateRecurrenceDates($validatedData);
            $validatedData['regenerated_dates'] = $regeneratedDates ? json_encode($regeneratedDates) : null;

            // 2. Update Maintenance & Activité
            $maintenance->update($validatedData);

            // Enregistrement du changement de statut
            if (isset($validatedData['status']) && $originalStatus !== $validatedData['status']) {
                MaintenanceStatusHistory::create([
                    'maintenance_id' => $maintenance->id,
                    'user_id' => Auth::id(),
                    'old_status' => $originalStatus,
                    'new_status' => $validatedData['status'],
                ]);
            }
          $last =$originalRegeneratedDates;
$new = $validatedData['regenerated_dates'];

$oldArray = is_string($last) ? json_decode($last, true) : $last;
$newArray = is_string($new) ? json_decode($new, true) : $new;

// 2. On nettoie (optionnel mais recommandé pour les dates)
// Parfois l'ordre peut changer, donc on trie
sort($oldArray);
sort($newArray);

            // Comparer les anciennes et nouvelles dates de récurrence
            if ($oldArray !== $newArray) {
                // Si les dates ont changé, on supprime et on recrée les activités
                Activity::where('maintenance_id', $maintenance->id)->delete();
                $activities = [];
                $startTime = Carbon::parse($maintenance->scheduled_start_date)->format('H:i:s');
                $endTime = Carbon::parse($maintenance->scheduled_end_date)->format('H:i:s');

                if ($regeneratedDates) {
                    foreach ($regeneratedDates as $date) {
                        $activityDate = Carbon::parse($date)->startOfDay();
                        $activityTitle = 'Maintenance sur ' . $maintenance->title;
                        // ... (le reste de la logique de création de titre reste identique)

                        $activities[] = Activity::create([
                            'maintenance_id' => $maintenance->id,
                            'title' => $activityTitle,
                            'assignable_type' => $maintenance->assignable_type,
                            'assignable_id' => $maintenance->assignable_id,
                            'status' => $maintenance->status === 'completed' ? 'completed' : 'scheduled',
                            'actual_start_time' => $activityDate->copy()->setTimeFromTimeString($startTime),
                            'actual_end_time' => $activityDate->copy()->setTimeFromTimeString($endTime),
                            'priority' => $maintenance->priority,
                            'user_id' => Auth::id(),
                        ]);
                    }
                } else {
                    // Recréation pour une seule activité si la récurrence est supprimée
                    $activityTitle = 'Maintenance sur ' . $maintenance->title;
                    // ... (logique de titre)
                    $activities[] = Activity::create([
                        'maintenance_id' => $maintenance->id,
                        'title' => $activityTitle,
                        'assignable_type' => $maintenance->assignable_type,
                        'assignable_id' => $maintenance->assignable_id,
                        'region_id' => $maintenance->region_id,
                        'status' => $maintenance->status === 'completed' ? 'completed' : 'scheduled',
                        'actual_start_time' => $maintenance->scheduled_start_date,
                        'actual_end_time' => $maintenance->scheduled_end_date,
                        'priority' => $maintenance->priority,
                        'user_id' => Auth::id(),
                    ]);
                }
            } else {

                // Si les dates n'ont pas changé, on met simplement à jour les activités existantes
                $activities = Activity::where('maintenance_id', $maintenance->id)->get();
                $activityTitle = 'Maintenance sur ' . $maintenance->title;
                if ($maintenance->equipments()->count() > 0) {
                    if ($maintenance->equipments()->count() == 1) {
                        $equipment = $maintenance->equipments()->first();
                        $regionName = $maintenance->networkNode->region->designation ?? 'N/A';
                        $zoneName = $maintenance->networkNode->zone->title ?? ($maintenance->networkNode->network->nomenclature ?? 'N/A');
                        $activityTitle = 'Maintenance sur ' . $equipment->designation . ' (' . $regionName . ' / ' . $zoneName . ')';
                    } else {
                        $regionName = $maintenance->region->designation ?? 'N/A';
                        $activityTitle = 'Maintenance sur ' . $maintenance->equipments()->count() . ' équipements (' . $regionName . ')';
                    }
                }

                foreach ($activities as $activity) {
                    $activity->fill([
                        'title' =>  $activityTitle,
                        'assignable_type' => $activity->assignable_type ?? $maintenance->assignable_type,
                        'assignable_id' => $activity->assignable_id ?? $maintenance->assignable_id,
                        'region_id' => $activity->region_id ?? $maintenance->region_id,
                        'status' => $activity->status ?? $maintenance->status,
                        'priority' => $activity->priority ?? $maintenance->priority,
                        'actual_start_time' => $activity->actual_start_time ?? (!$regeneratedDates ? $maintenance->scheduled_start_date : null),
                        'actual_end_time' => $activity->actual_end_time ?? (!$regeneratedDates ? $maintenance->scheduled_end_date : null),
                    ])->save();
                }
            }

            // 3. Sync Équipements
            if (isset($validatedData['equipment_ids'])) {
                $syncData = collect($validatedData['equipment_ids'])->mapWithKeys(function ($id) use ($maintenance) {
                    return [(int) $id => ['network_node_id' => $maintenance->network_node_id]];
                })->toArray();
                $maintenance->equipments()->sync($syncData);
                foreach ($activities as $activity) {
                    $activity->equipment()->sync($validatedData['equipment_ids']);
                }
            }

            // 4. Instructions (Clean & Recreate)
            $maintenance->instructions()->delete();
            foreach ($activities as $activity) {
                $this->processInstructions($maintenance, $activity, $validatedData['node_instructions'] ?? []);
            }

            // 5. Service Order
            $this->processServiceOrder($maintenance, $validatedData);

            // 6. NetworkNode Date Management
            if ($oldNodeId && $oldNodeId != $maintenance->network_node_id) {
                NetworkNode::where('id', $oldNodeId)->update(['next_maintenance_date' => null]);
            }
            if ($maintenance->network_node_id) {
                NetworkNode::where('id', $maintenance->network_node_id)
                    ->update(['next_maintenance_date' => $maintenance->scheduled_start_date]);
            }

            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'Maintenance mise à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur Update Maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour.');
        }
    }

    /**
     * Supprime une maintenance.
     */
    public function destroy(Maintenance $maintenance)
    {
        DB::beginTransaction();
        try {
            $maintenance->equipments()->detach();
            $maintenance->instructions()->delete();
            Activity::where('maintenance_id', $maintenance->id)->delete();

            $maintenance->delete();
            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'Maintenance supprimée.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur Delete Maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    // --- HELPER METHODS ---

    private function validateMaintenance(Request $request)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'network_node_id' => 'nullable|exists:network_nodes,id',
            'network_id' => 'nullable|exists:networks,id',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipment,id',
            'assignable_type' => ['nullable', 'string', Rule::in(['App\Models\User', 'App\Models\Team'])],
            'assignable_id' => 'nullable|integer',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'estimated_duration' => 'nullable|integer',
            'cost' => 'nullable|numeric',
            'region_id' => 'nullable|exists:regions,id',
            'recurrence_type' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer',
            'recurrence_days' => 'nullable|array',
            'reminder_days' => 'nullable|integer',
            'reminder_interval' => 'nullable|integer',
            'recurrence_day_of_month' => 'nullable|integer',
            'monthly_recurrence_type' => 'nullable|string',
            'recurrence_day' => 'nullable|integer',
            'node_instructions' => 'nullable|array',
            'node_instructions.*.*.label' => 'required|string|max:255',
            'node_instructions.*.*.type' => 'required|string',
            'node_instructions.*.*.is_required' => 'boolean',
            'service_order_cost' => 'nullable|numeric|min:0',
            'service_order_description' => 'nullable|string|required_with:service_order_cost',
        ]);
    }

    private function processInstructions($maintenance, $activity, $nodeInstructions)
    {
        foreach ($nodeInstructions as $equipmentId => $instructions) {
            foreach ($instructions as $data) {
                $mInst = $maintenance->instructions()->create([
                    'equipment_id' => (int) $equipmentId,
                    'label' => $data['label'],
                    'type' => $data['type'],
                    'is_required' => $data['is_required'] ?? false,
                ]);

                $activity->activityInstructions()->create([
                    'label' => $mInst->label,
                    'type' => $mInst->type,
                    'is_required' => $mInst->is_required,
                ]);
            }
        }
    }

    private function processServiceOrder($maintenance, $data)
    {
        if (isset($data['service_order_cost']) && $data['service_order_cost'] > 0) {
            $serviceOrder = ServiceOrder::updateOrCreate(
                ['maintenance_id' => $maintenance->id],
                [
                    'description' => $data['service_order_description'] ?? 'Prestation pour #' . $maintenance->id,
                    'cost' => $data['service_order_cost'],
                    'status' => 'completed',
                    'order_date' => now(),
                    'actual_completion_date' => now(),
                ]
            );

            $serviceOrder->expenses()->updateOrCreate(
                ['category' => 'external_service'],
                [
                    'description' => 'Coût prestation : ' . $serviceOrder->description,
                    'amount' => $serviceOrder->cost,
                    'expense_date' => now(),
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                ]
            );
        } else {
            $existingSO = ServiceOrder::where('maintenance_id', $maintenance->id)->first();
            if ($existingSO) {
                $existingSO->expenses()->delete();
                $existingSO->delete();
            }
        }
    }

    private function transformForTreeSelect($equipments)
    {
        return $equipments->map(function ($equipment) {
            return [
                'id' => $equipment->id,
                'key' => (string) $equipment->id,
                'label' => $equipment->designation,
                'children' => $equipment->children->isNotEmpty()
                    ? $this->transformForTreeSelect($equipment->children)
                    : [],
            ];
        });
    }
    private function generateRecurrenceDates(array $data): ?array
{
    $recurrenceType = $data['recurrence_type'] ?? null;
    $startDate = isset($data['scheduled_start_date']) ? Carbon::parse($data['scheduled_start_date'])->startOfDay() : null;
    $endDate = isset($data['scheduled_end_date']) ? Carbon::parse($data['scheduled_end_date'])->endOfDay() : null;

    if (!$recurrenceType || $recurrenceType === 'none' || !$startDate || !$endDate || $startDate->gt($endDate)) {
        return null;
    }

    $dates = [];
    $maxOccurrences = 500;
    $interval = max(1, (int)($data['recurrence_interval'] ?? 1));

    // --- LOGIQUE DAILY (Journalière) ---
    if ($recurrenceType === 'daily') {
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate) && count($dates) < $maxOccurrences) {
            $dates[] = $currentDate->copy();
            $currentDate->addDays($interval);
        }
    }

    // --- LOGIQUE WEEKLY (Hebdomadaire) ---
  // --- CAS 2 : WEEKLY (HEBDOMADAIRE) ---
  // --- CAS 2 : WEEKLY (HEBDOMADAIRE) ---
    // --- CAS 2 : WEEKLY (HEBDOMADAIRE) ---
// --- CAS 2 : WEEKLY (HEBDOMADAIRE) ---
elseif ($recurrenceType === 'weekly') {
    $targetDays = (array)($data['recurrence_days'] ?? []);
    if (empty($targetDays)) return [];

    // Sécurité : Si l'intervalle est 0 ou vide, on force à 1 pour ne rien sauter
    $interval = 1;

    // On commence l'analyse au lundi de la semaine de départ
    $currentWeek = $startDate->copy()->startOfWeek(Carbon::MONDAY);

    while ($currentWeek->lte($endDate) && count($dates) < $maxOccurrences) {

        // On teste les 7 jours de la semaine courante
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $currentWeek->copy()->addDays($i)->startOfDay();
            $dayValue = $checkDate->dayOfWeek; // 0 (Dim) à 6 (Sam)

            // Validation :
            // 1. Le jour est-il coché dans Vue.js ?
            // 2. Est-il compris entre la date de début et de fin ?
            if (in_array($dayValue, $targetDays)
                && $checkDate->gte($startDate)
                && $checkDate->lte($endDate)) {
                $dates[] = $checkDate->copy();
            }
        }

        // Si intervalle est 1, on passe à la semaine suivante (0 devient 1)
        $currentWeek->addWeeks($interval);
    }
}
    // --- LOGIQUE MENSUELLE / ANNUELLE (Votre code existant conservé) ---
    elseif (in_array($recurrenceType, ['monthly', 'quarterly', 'biannual', 'annual'])) {
        $monthlyType = $data['monthly_recurrence_type'] ?? 'day_of_month';
        $anchorDate = $startDate->copy();

        if ($monthlyType === 'day_of_month') {
            $targetDay = (int)($data['recurrence_day_of_month'] ?? $startDate->day);
            if ($anchorDate->day > $targetDay) {
                $anchorDate->addMonth()->day(1);
            }
            $anchorDate->day(min($targetDay, $anchorDate->daysInMonth));
        } else {
            $targetDayOfWeek = (int)($data['recurrence_day'] ?? 0);
            while ($anchorDate->dayOfWeek !== $targetDayOfWeek) {
                $anchorDate->addDay();
            }
        }

        if ($anchorDate->gt($endDate)) return [];

        $currentDate = $anchorDate->copy();
        $ordinal = (int) ceil($currentDate->day / 7);
        $dayOfWeek = $currentDate->dayOfWeek;

        while ($currentDate->lte($endDate) && count($dates) < $maxOccurrences) {
            $dates[] = $currentDate->copy();

            $monthsToAdd = match($recurrenceType) {
                'monthly'   => $interval,
                'quarterly' => 3 * $interval,
                'biannual'  => 6 * $interval,
                'annual'    => 12 * $interval,
                default     => 1
            };

            if ($monthlyType === 'day_of_month') {
                $targetDay = (int)($data['recurrence_day_of_month'] ?? $anchorDate->day);
                $currentDate->addMonths($monthsToAdd);
                $currentDate->day(min($targetDay, $currentDate->daysInMonth));
            } else {
                $currentDate->addMonths($monthsToAdd)->startOfMonth();
                try {
                    $currentDate = $currentDate->nthOfMonth($ordinal, $dayOfWeek);
                } catch (\Exception $e) {
                    $currentDate = $currentDate->endOfMonth()->previous($dayOfWeek);
                }
            }
        }
    }

    // Tri pour garantir l'ordre chronologique (important pour le mode hebdomadaire)
    return collect($dates)
        ->unique(fn($d) => $d->format('Y-m-d'))
        ->sort()
        ->values()
        ->map(fn($d) => $d->toIso8601String())
        ->toArray();
}
// private function generateRecurrenceDates(array $data): ?array
// {
//     $recurrenceType = $data['recurrence_type'] ?? null;
//     $startDate = isset($data['scheduled_start_date']) ? Carbon::parse($data['scheduled_start_date'])->startOfDay() : null;
//     $endDate = isset($data['scheduled_end_date']) ? Carbon::parse($data['scheduled_end_date'])->endOfDay() : null;

//     if (!$recurrenceType || $recurrenceType === 'none' || !$startDate || !$endDate || $startDate->gt($endDate)) {
//         return null;
//     }

//     $dates = [];
//     $interval = max(1, (int)($data['recurrence_interval'] ?? 1));
//     $monthlyType = $data['monthly_recurrence_type'] ?? 'day_of_month';

//     // --- ÉTAPE 1 : TROUVER LA TOUTE PREMIÈRE DATE (L'ANCRE) ---
//     $anchorDate = $startDate->copy();

//     if ($monthlyType === 'day_of_month') {
//         // On cherche le jour X le plus proche (soit ce mois-ci, soit le suivant)
//         $targetDay = (int)($data['recurrence_day_of_month'] ?? $startDate->day);

//         // Si le jour cible est déjà passé ce mois-ci, on passe au mois suivant
//         if ($anchorDate->day > $targetDay) {
//             $anchorDate->addMonth()->day(1);
//         }
//         // On se positionne sur le jour cible (en gérant les mois courts comme février)
//         $anchorDate->day(min($targetDay, $anchorDate->daysInMonth));
//     }
//     else {
//         // On cherche le DIMANCHE le plus proche à partir de la date de début
//         $targetDayOfWeek = (int)($data['recurrence_day'] ?? 0); // 0 = Dimanche
//         while ($anchorDate->dayOfWeek !== $targetDayOfWeek) {
//             $anchorDate->addDay();
//         }
//     }

//     // Si l'ancre trouvée dépasse la date de fin, on s'arrête
//     if ($anchorDate->gt($endDate)) {
//         return [];
//     }

//     // --- ÉTAPE 2 : GÉNÉRER LA SUITE À PARTIR DE CETTE ANCRE ---
//     $currentDate = $anchorDate->copy();

//     // Pour le mode "day_of_week", on mémorise l'ordre (ex: 2ème dimanche)
//     // pour le reproduire les mois suivants
//     $ordinal = (int) ceil($currentDate->day / 7);
//     $dayOfWeek = $currentDate->dayOfWeek;

//     while ($currentDate->lte($endDate) && count($dates) < 500) {
//         $dates[] = $currentDate->copy();

//         // Calcul du saut de mois
//         $monthsToAdd = match($recurrenceType) {
//             'monthly'   => $interval,
//             'quarterly' => 3 * $interval,
//             'biannual'  => 6 * $interval,
//             'annual'    => 12 * $interval,
//             default     => 1
//         };

//         if ($monthlyType === 'day_of_month') {
//             $targetDay = (int)($data['recurrence_day_of_month'] ?? $anchorDate->day);
//             $currentDate->addMonths($monthsToAdd);
//             $currentDate->day(min($targetDay, $currentDate->daysInMonth));
//         } else {
//             // On saute de X mois et on recherche le même Dimanche (ex: le 2ème)
//             $currentDate->addMonths($monthsToAdd)->startOfMonth();
//             try {
//                 $currentDate = $currentDate->nthOfMonth($ordinal, $dayOfWeek);
//             } catch (\Exception $e) {
//                 // Si le 5ème dimanche n'existe pas, on prend le dernier
//                 $currentDate = $currentDate->endOfMonth()->previous($dayOfWeek);
//             }
//         }
//     }

//     return collect($dates)
//         ->map(fn($d) => $d->toIso8601String())
//         ->toArray();
// }
    // private function generateRecurrenceDates(array $data): ?array
    // {
    //     $recurrenceType = $data['recurrence_type'] ?? null;
    //     $startDate = isset($data['scheduled_start_date']) ? Carbon::parse($data['scheduled_start_date'])->startOfDay() : null;
    //     $endDate = isset($data['scheduled_end_date']) ? Carbon::parse($data['scheduled_end_date'])->endOfDay() : null;

    //     if (!$recurrenceType || !$startDate || !$endDate || $startDate->gt($endDate)) {
    //         return null;
    //     }

    //     $dates = [];
    //     $maxOccurrences = 500;
    //     $interval = max(1, (int)($data['recurrence_interval'] ?? 1));

    //     $currentDate = $startDate->copy();

    //     // Store the original start date to ensure we don't generate dates before it
    //     $originalStartDate = $startDate->copy();


    //     while ($currentDate->lte($endDate) && count($dates) < $maxOccurrences) {
    //         switch ($recurrenceType) {
    //             case 'daily':
    //                 // Add the current date if it's within the range
    //                 if ($currentDate->gte($startDate) && $currentDate->lte($endDate)) {
    //                     $dates[] = $currentDate->copy();
    //                 }
    //                 // Move to the next occurrence based on the interval
    //                 $currentDate->addDays($interval);
    //                 break;

    //             case 'weekly':
    //                 $days = (array)($data['recurrence_days'] ?? []); // Days of the week (0 for Sunday, 1 for Monday, etc.)
    //                 if (empty($days)) {
    //                     // If no specific days are selected for weekly recurrence, break to avoid infinite loop
    //                     break 2; // Break out of both the switch and the while loop
    //                 }

    //                 // Find the first day of the week that matches a selected day and is on or after the start date
    //                 $startOfWeek = $currentDate->copy()->startOfWeek(); // Get the Sunday of the current week

    //                 for ($i = 0; $i < 7; $i++) {
    //                     $dayInWeek = $startOfWeek->copy()->addDays($i);

    //                     // Check if this day is one of the selected recurrence days
    //                     if (in_array($dayInWeek->dayOfWeek, $days)) {
    //                         // Only add if it's within the overall scheduled range
    //                         if ($dayInWeek->gte($startDate) && $dayInWeek->lte($endDate)) {
    //                             $dates[] = $dayInWeek->copy();
    //                         }
    //                     }
    //                 }
    //                 // Move to the next week based on the interval
    //                 $currentDate->addWeeks($interval);
    //                 break;

    //             case 'monthly':
    //             case 'quarterly':
    //             case 'biannual':
    //             case 'annual':
    //                 $monthlyType = $data['monthly_recurrence_type'] ?? 'day_of_month';
    //                 $targetDayOfMonth = (int)($data['recurrence_day_of_month'] ?? 1); // Default to 1st day
    //                 $targetDayOfWeek = (int)($data['recurrence_day'] ?? 2); // Default to Monday (1)

    //                 // Determine the month to start checking from.
    //                 // It should be the month of $currentDate, but not before $originalStartDate.
    //                 $checkMonth = $currentDate->copy()->startOfMonth();
    //                 if ($checkMonth->lt($originalStartDate->copy()->startOfMonth())) {
    //                     $checkMonth = $originalStartDate->copy()->startOfMonth();
    //                 }

    //                 if ($monthlyType === 'day_of_month') {
    //                     // Recur on a specific day of the month (e.g., 15th of every month)
    //                     $targetDate = $checkMonth->copy()->day(min($targetDayOfMonth, $checkMonth->daysInMonth));

    //                     // Only add if it's on or after the original start date and within the overall scheduled range
    //                     if ($targetDate->gte($originalStartDate) && $targetDate->lte($endDate)) {
    //                         $dates[] = $targetDate;
    //                     }
    //                 }
    //                 elseif ($monthlyType === 'day_of_week') {
    //                     // Recur on a specific day of the week (e.g., first Monday of the month)
    //                     // $targetDayOfMonth here actually represents the Nth occurrence of the day of week (e.g., 1st, 2nd, 3rd, 4th, last)
    //                     $occurrence = $targetDayOfMonth; // This is actually the 'recurrence_day_of_month' from validation

    //                     $foundCount = 0;
    //                     $lastFoundDate = null;
    //                     $addedThisMonth = false;

    //                     // Iterate through the month to find the Nth occurrence of the target day of week
    //                     for ($i = 1; $i <= $checkMonth->daysInMonth; $i++) {
    //                         $checkDate = $checkMonth->copy()->day($i);

    //                         // Only consider dates on or after the original start date
    //                         if ($checkDate->lt($originalStartDate)) {
    //                             continue;
    //                         }

    //                         if ($checkDate->dayOfWeek === $targetDayOfWeek) {
    //                             $foundCount++;
    //                             $lastFoundDate = $checkDate->copy();
    //                             if ($foundCount === $occurrence) {
    //                                 if ($checkDate->lte($endDate)) {
    //                                     $dates[] = $checkDate;
    //                                     $addedThisMonth = true;
    //                                 }
    //                                 break; // Found the Nth occurrence
    //                             }
    //                         }
    //                     }
    //                     // Handle "last" occurrence if $occurrence is 5 or greater (or a specific value for "last")
    //                     if (!$addedThisMonth && $occurrence >= 5 && $lastFoundDate) { // If user selected 5th but only 4 exist, use last
    //                          if ($lastFoundDate->gte($originalStartDate) && $lastFoundDate->lte($endDate)) {
    //                             $dates[] = $lastFoundDate;
    //                         }
    //                     }
    //                 }

    //                 // Advance to the next month for recurrence based on type
    //                 $monthsToAdd = match($recurrenceType) {
    //                     'monthly' => $interval,
    //                     'quarterly' => 3 * $interval, // Quarterly means every 3 months, multiplied by interval
    //                     'biannual' => 6 * $interval,  // Biannual means every 6 months, multiplied by interval
    //                     'annual' => 12 * $interval,   // Annual means every 12 months, multiplied by interval
    //                     default => $interval // Fallback, though should be covered
    //                 };
    //                 $currentDate->addMonths($monthsToAdd);
    //                 break;

    //             default:
    //                 // If recurrence type is unknown, stop processing
    //                 break 2; // Break out of both the switch and the while loop
    //         }
    //     }
    //     return collect($dates)->unique(fn($d) => $d->format('Y-m-d'))->values()->map(fn($d) => $d->toIso8601String())->toArray();
    // }
}
