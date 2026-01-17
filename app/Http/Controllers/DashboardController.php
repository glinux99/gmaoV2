<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Role;
use App\Models\User;
use App\Models\Task;
use App\Models\Activity;
use App\Models\SparePartMovement;
use App\Models\ServiceOrder;
use App\Models\SparePart;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\Zone;
use App\Models\MaintenanceStatusHistory;
use Carbon\CarbonPeriod;
use App\Models\InstructionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use App\Models\Equipment;
use App\Models\Expenses;
use App\Models\Maintenance;
use App\Models\StockMovement;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Seuls les utilisateurs avec la permission 'view-dashboard' peuvent accéder à ce contrôleur.
        // Le middleware s'applique à toutes les méthodes du contrôleur.
        $this->middleware('can:view-dashboard');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Gestion de la période de filtrage
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'equipment_id' => 'nullable|integer|exists:equipment,id',
            'zone_id' => 'nullable|integer|exists:zones,id',
        ]);

        // Définition des périodes
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::create(2018, 1, 1)->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // Récupération des filtres optionnels
        $equipmentId = $request->input('equipment_id');
        $zoneId = $request->input('zone_id');

        // Période précédente (pour comparaison des métriques)
        $previousStartDate = null;
        $previousEndDate = null;
        if ($startDate && $endDate) {
            $previousStartDate = $startDate->copy()->subMonth();
            $previousEndDate = $endDate->copy()->subMonth();
        }

        // Fonction helper pour déterminer la colonne de formatage de date selon le SGBD
        $getDateFormat = function ($column, $format = '%Y-%m-%d') {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                return "DATE_FORMAT($column, '$format')";
            }
            if ($driver === 'pgsql') {
                return "TO_CHAR($column, 'YYYY-MM-DD')"; // Utiliser TO_CHAR pour PostgreSQL
            }
            return "DATE($column)"; // Fallback (e.g., SQLite)
        };

        // Fonction helper pour calculer le temps en heures/minutes
        $getTimeDiffExpression = function ($endColumn, $startColumn, $unit = 'minute') {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $seconds = "TIMESTAMPDIFF(SECOND, $startColumn, $endColumn)";
            } elseif ($driver === 'pgsql') {
                $seconds = "EXTRACT(EPOCH FROM ($endColumn - $startColumn))";
            } else { // SQLite
                $seconds = "CAST(strftime('%s', $endColumn) - strftime('%s', $startColumn) AS REAL)";
            }

            return $unit === 'hour' ? "$seconds / 3600" : "$seconds / 60";
        };

        // --- Basic data (Métriques globales) ---
        $usersCount = User::count();
        $rolesCount = Role::count();
        $permissionsCount = Permission::count();

        $activeTasksQuery = Activity::query();
        if ($startDate && $endDate) $activeTasksQuery->whereBetween('created_at', [$startDate, $endDate]);
        $activeTasks = $activeTasksQuery->whereIn('status', ['scheduled', 'in_progress', 'en cours', 'planifiée'])->count();

        // Temps moyen d'intervention (calculé directement)
        // MTTR (Mean Time To Repair) : Temps moyen pour compléter une activité.
        $avgInterventionQuery = Activity::where('status', 'completed');
        if ($startDate && $endDate) {
            $avgInterventionQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        }
        $averageInterventionTimeInMinutes = $avgInterventionQuery->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));

        $averageInterventionTime = $averageInterventionTimeInMinutes ?
            round($averageInterventionTimeInMinutes) . 'm' : '0m';

        // --- Helper function pour le calcul du changement de métrique (%) ---
        $calculateMetric = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? '+100%' : '0%';
            }
            $change = (($current - $previous) / $previous) * 100;
            return ($change >= 0 ? '+' : '') . round($change) . '%';
        };

        // --- Helper function pour générer les données de graphique (Sparklines) ---
        $generateChartData = function ($model, $dateColumn, $period) use ($getDateFormat) {
            $query = $model::query();
            $dateFormat = $getDateFormat($dateColumn, '%Y-%m-%d');

            if ($period['start'] && $period['end']) {
                $query->whereBetween($dateColumn, [$period['start'], $period['end']]);
            }

            $data = $query->select(DB::raw("$dateFormat as date"), DB::raw('count(*) as count'))
                 ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'labels' => $data->pluck('date'),
                'datasets' => [['data' => $data->pluck('count')]]
            ];
        };

        // --- Calculs pour les Sparklines ---
        // --- NOUVELLE LOGIQUE : Combinaison des Tâches, Maintenances et Activités autonomes ---

        // Fonction pour compter les entités sur une période
        $countEntities = function ($period) {
            $tasks = Task::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $maintenances = Maintenance::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $standaloneActivities = Activity::whereNull('task_id')
                ->whereNull('maintenance_id')
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count();
            return $tasks + $maintenances + $standaloneActivities;
        };

        // Fonction pour générer les données de graphique en combinant les modèles
        $generateCombinedChartData = function ($period) use ($getDateFormat) {
            $dateFormat = $getDateFormat('created_at', '%Y-%m-%d');

            $tasksData = Task::whereBetween('created_at', [$period['start'], $period['end']])->select(DB::raw("$dateFormat as date"), DB::raw('count(*) as count'))->groupBy('date');
            $maintenancesData = Maintenance::whereBetween('created_at', [$period['start'], $period['end']])->select(DB::raw("$dateFormat as date"), DB::raw('count(*) as count'))->groupBy('date');
            $activitiesData = Activity::whereNull('task_id')->whereNull('maintenance_id')->whereBetween('created_at', [$period['start'], $period['end']])->select(DB::raw("$dateFormat as date"), DB::raw('count(*) as count'))->groupBy('date');

            $combinedData = $tasksData->unionAll($maintenancesData)->unionAll($activitiesData)->get()->groupBy('date')->map(function ($group) {
                return $group->sum('count');
            })->sortKeys();

            return [
                'labels' => $combinedData->keys(),
                'datasets' => [['data' => $combinedData->values()]]
            ];
        };

        // Tâches créées (maintenant "Interventions créées")
        $activeTasksCurrentQuery = Task::query();
        if ($startDate && $endDate) {
            $activeTasksCurrentQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $activeTasksCurrentCount = $activeTasksCurrentQuery->count();

        $activeTasksPreviousQuery = Task::query();
        if ($previousStartDate && $previousEndDate) {
            $activeTasksPreviousQuery->whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        }
        $activeTasksPreviousCount = $activeTasksPreviousQuery->count();

        // Utilisation des nouvelles fonctions pour un calcul combiné
        $activeTasksCurrentCount = $countEntities(['start' => $startDate, 'end' => $endDate]);
        $activeTasksPreviousCount = $countEntities(['start' => $previousStartDate, 'end' => $previousEndDate]);

        // Temps Total Passé (en heures)
        $timeSpentCurrentQuery = Activity::query();
        if ($startDate && $endDate) $timeSpentCurrentQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        $timeSpentCurrent = $timeSpentCurrentQuery->sum(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        $timeSpentPreviousQuery = Activity::query();
        if ($previousStartDate && $previousEndDate) $timeSpentPreviousQuery->whereBetween('actual_end_time', [$previousStartDate, $previousEndDate]);
        $timeSpentPrevious = $timeSpentPreviousQuery->sum(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // Temps Moyen d'Intervention (en minutes)
        $avgTimeCurrent = $timeSpentCurrentQuery->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));
        $avgTimePrevious = $timeSpentPreviousQuery->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));

        // --- NOUVEAU : Calcul pour le Backlog ---
        $backlogQuery = fn($period) => Activity::whereIn('status', ['scheduled', 'in_progress', 'awaiting_resources', 'pending', 'en cours', 'planifiée', 'en attente'])
                                                ->where('created_at', '<', $period['end']);

        $backlogCurrentCount = $backlogQuery(['start' => $startDate, 'end' => $endDate])->count();
        $backlogPreviousCount = $backlogQuery(['start' => $previousStartDate, 'end' => $previousEndDate])->count();
        $backlogChartData = $generateChartData(new Activity, 'created_at', ['start' => $startDate, 'end' => $endDate]);


        $sparklineData = [
            // 'users' => [
            //     'value' => $usersCurrentCount,
            //     'metric' => $calculateMetric($usersCurrentCount, $usersPreviousCount),
            //     'chartData' => $generateChartData(new User, 'created_at', ['start' => $startDate, 'end' => $endDate])
            // ],
            'activeTasks' => [
                'value' => $activeTasksCurrentCount,
                'metric' => $calculateMetric($activeTasksCurrentCount, $activeTasksPreviousCount),
                'chartData' => $generateCombinedChartData(['start' => $startDate, 'end' => $endDate])
            ],
            'backlog' => [
                'value' => $backlogCurrentCount,
                'metric' => $calculateMetric($backlogCurrentCount, $backlogPreviousCount),
                'chartData' => $backlogChartData
            ],
            'timeSpent' => [
                'value' => round($timeSpentCurrent, 1) . 'h',
                'metric' => $calculateMetric($timeSpentCurrent, $timeSpentPrevious),
                'chartData' => $generateChartData(new Activity, 'actual_end_time', ['start' => $startDate, 'end' => $endDate])
            ],
            'averageInterventionTime' => [
                'value' => round($avgTimeCurrent) . 'm',
                'metric' => $calculateMetric($avgTimeCurrent, $avgTimePrevious),
                'chartData' => $generateChartData(new Activity, 'actual_end_time', ['start' => $startDate, 'end' => $endDate])
            ],
        ];
        // return $sparklineData;
        // --- Données Financières (Dépenses de Consommation) ---

        // Dépenses Pièces Détachées (coût des pièces UTILISÉES dans les activités)
        $depensesPiecesDetacheesQuery = DB::table('spare_part_activities')
            ->join('spare_parts', 'spare_part_activities.spare_part_id', '=', 'spare_parts.id')
            ->where('spare_part_activities.type', 'used');
        if ($startDate && $endDate) {
            $depensesPiecesDetacheesQuery->whereBetween('spare_part_activities.created_at', [$startDate, $endDate]);
        }
        $depensesPiecesDetachees = $depensesPiecesDetacheesQuery->sum(DB::raw('spare_part_activities.quantity_used * spare_parts.price'));

        // Dépenses Prestation (Coût des ServiceOrder)
        $depensesPrestationQuery = ServiceOrder::where('status', 'completed');
        if ($startDate && $endDate) {
            $depensesPrestationQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $depensesPrestation = $depensesPrestationQuery->sum('cost');

        // Dépenses validées (Perte Estimée)
        $expensesTotalQuery = Expenses::where('status', 'approved')->orWhere('status', 'paid');
        if ($startDate && $endDate) {
            $expensesTotalQuery->whereBetween('expense_date', [$startDate, $endDate]);
        }
        $expensesTotal = $expensesTotalQuery->sum('amount');
            // dd($expensesTotal);
        // --- Calcul du Budget Total (Dépenses d'investissement/achat) ---

        // Coût des équipements achetés sur la période
        $equipmentPurchaseCostQuery = Equipment::query();
        if ($startDate && $endDate) {
            $equipmentPurchaseCostQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $equipmentPurchaseCost = $equipmentPurchaseCostQuery->sum('price');

        // Coût des pièces détachées entrées en stock sur la période
        $sparePartsInflowCostQuery = StockMovement::where('stock_movements.movable_type', SparePart::class)
            ->where('stock_movements.type', 'entry')
            ->join('spare_parts', function ($join) {
                $join->on('stock_movements.movable_id', '=', 'spare_parts.id')
                     ->where('stock_movements.movable_type', '=', SparePart::class);
            });
        if ($startDate && $endDate) {
            $sparePartsInflowCostQuery->whereBetween('stock_movements.created_at', [$startDate, $endDate]);
        }
        $sparePartsInflowCost = $sparePartsInflowCostQuery->sum(DB::raw('stock_movements.quantity * spare_parts.price'));

        $budgetTotalCalculated = $equipmentPurchaseCost + $sparePartsInflowCost + $depensesPrestation;
        // dd($budgetTotalCalculated);
        //  --- Coûts de Maintenance ---
        // Coût de la main d'œuvre (basé sur le champ 'labor_cost' des maintenances terminées)
        $laborCostQuery = Maintenance::where('status', 'completed', 'in_progress');
        if ($startDate && $endDate) {
            // On se base sur la date de fin de la maintenance pour le filtre
            $laborCostQuery->whereBetween('scheduled_end_date', [$startDate, $endDate]);
        }
        $laborCost = $laborCostQuery->sum('labor_cost');

        $maintenanceCostQuery = Maintenance::query();
        if ($startDate && $endDate) {
            $maintenanceCostQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalMaintenanceCost = $maintenanceCostQuery->sum('cost');

        // Calcul du coût de la main d'œuvre pour la maintenance

        // --- Mouvements de Pièces Détachées ---
        $movementsQuery = StockMovement::where('movable_type', SparePart::class);
        if ($startDate && $endDate) {
            $movementsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $movements = $movementsQuery->select(
        DB::raw("DATE(created_at) as movement_date"), // Alias explicite
        DB::raw("SUM(CASE WHEN type = 'entry' THEN quantity ELSE 0 END) as entries"),
        DB::raw("SUM(CASE WHEN type = 'exit' THEN quantity ELSE 0 END) as exits")
    )
    ->groupBy('movement_date') // On groupe par l'alias défini au-dessus
    ->orderBy('movement_date', 'ASC')
    ->get();

        $sparePartsMovement = [
            'labels' => $movements->pluck('movement_date'),
            'entries' => $movements->pluck('entries'),
            'exits' => $movements->pluck('exits'),
        ];


        // --- NOUVEAU : Données pour la rotation de stock par article ---
        $itemMovementsQuery = StockMovement::with('movable');
        if ($startDate && $endDate) {
            $itemMovementsQuery->whereBetween('date', [$startDate, $endDate]);
        }
        $itemMovements = $itemMovementsQuery->select(
                'movable_id',
                'movable_type',
                DB::raw("SUM(CASE WHEN type = 'entry' THEN quantity ELSE 0 END) as total_entries"),
                DB::raw("SUM(CASE WHEN type = 'exit' THEN quantity ELSE 0 END) as total_exits")
            )
            ->groupBy('movable_id', 'movable_type')
            // ->orderByRaw('total_entries + total_exits DESC') // Ordonner par le total des mouvements
            ->limit(7) // Limiter aux 7 articles les plus mouvementés pour la clarté du graphique
            ->get();

        $stockRotationData = [
            'labels' => $itemMovements->map(function ($item) {
                if (!$item->movable) {
                    return 'Item #' . $item->movable_id;
                }
                // Retourne la référence, le tag, le numéro de série ou la désignation
                return $item->movable->reference
                    ?? $item->movable->tag
                    ?? $item->movable->serial_number
                    ?? $item->movable->designation
                    ?? 'Item Inconnu';
            }),
            'entries' => $itemMovements->pluck('total_entries'),
            'exits' => $itemMovements->pluck('total_exits'),
        ];

        // --- NOUVEAU : Totaux pour les cartes de flux de stock ---
        $totalStockInQuery = StockMovement::where('type', 'entry');
        if ($startDate && $endDate) {
            $totalStockInQuery->whereBetween('date', [$startDate, $endDate]);
        }
        $totalStockIn = $totalStockInQuery->sum('quantity');

        $totalStockOutQuery = StockMovement::where('type', 'exit');
        if ($startDate && $endDate) {
            $totalStockOutQuery->whereBetween('date', [$startDate, $endDate]);
        }
        $totalStockOut = $totalStockOutQuery->sum('quantity');

        // --- Tâches par Statut et Priorité (Graphiques en secteurs/barres) ---

        $tasksByStatusQuery = Task::select('status', DB::raw('count(*) as total'));
        if ($startDate && $endDate) $tasksByStatusQuery->whereBetween('created_at', [$startDate, $endDate]);
        $tasksByStatus = $tasksByStatusQuery->groupBy('status')->get()->pluck('total', 'status')->toArray();
        $tasksByStatusChart = [
            'labels' => array_keys($tasksByStatus),
            'data' => array_values($tasksByStatus),
        ];

        $tasksByPriorityQuery = Task::select('priority', DB::raw('count(*) as total'));
        if ($startDate && $endDate) $tasksByPriorityQuery->whereBetween('created_at', [$startDate, $endDate]);
        $tasksByPriority = $tasksByPriorityQuery->groupBy('priority')->get()->pluck('total', 'priority')->toArray();
        $tasksByPriorityChart = [
            'labels' => array_keys($tasksByPriority),
            'data' => array_values($tasksByPriority),
        ];

        // --- Volume Mensuel & Temps de Résolution Moyen (Graphique combiné) ---

        // **CORRECTION de l'ambiguïté :** On spécifie la table 'tasks' pour la première requête (volume)
        $monthlyTaskFormat = $getDateFormat('tasks.created_at', '%Y-%m');

        $monthlyDataQuery = Task::query();
        if ($startDate && $endDate) $monthlyDataQuery->whereBetween('created_at', [$startDate, $endDate]);

        $monthlyData = $monthlyDataQuery->select(
                DB::raw("$monthlyTaskFormat as month"),
                DB::raw("SUM(CASE WHEN maintenance_type = 'Corrective' THEN 1 ELSE 0 END) as stopped"),
                DB::raw("SUM(CASE WHEN maintenance_type = 'Préventive' THEN 1 ELSE 0 END) as degraded"),
                DB::raw("SUM(CASE WHEN maintenance_type = 'Améliorative' THEN 1 ELSE 0 END) as improvement")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $resolutionExpression = $getTimeDiffExpression('activities.actual_end_time', 'activities.actual_start_time', 'hour');

        // **CORRECTION de l'ambiguïté :** On spécifie la table 'activities' pour la seconde requête (résolution)
        $monthlyActivityFormat = $getDateFormat('activities.created_at', '%Y-%m');

        $resolutionDataQuery = Activity::join('tasks', 'activities.task_id', '=', 'tasks.id');
        if ($startDate && $endDate) $resolutionDataQuery->whereBetween('activities.created_at', [$startDate, $endDate]);

        $resolutionData = $resolutionDataQuery->select(
                DB::raw("$monthlyActivityFormat as month"),
                DB::raw("AVG($resolutionExpression) as avg_resolution_hours")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()->pluck('avg_resolution_hours', 'month');



        // Harmonisation des données pour le graphique (utilise les mois des tâches pour les labels)
        $monthlyVolumeData = [
            'labels' => $monthlyData->pluck('month'),
            'stopped' => $monthlyData->pluck('stopped'),
            'degraded' => $monthlyData->pluck('degraded'),
            'improvement' => $monthlyData->pluck('improvement'),
            // Utiliser les données de résolution correspondantes (Attention: les mois pourraient ne pas être alignés si le filtre est court)
            'resolutionTime' => $monthlyData->pluck('month')->map(
                fn ($month) => round($resolutionData->get($month) ?? 0, 1)
            ),
        ];

        // --- Pannes par Type (Priorité des Correctives) & Interventions par Type de Maintenance ---
        $failuresDataQuery = Task::where('maintenance_type', 'Corrective')->select('priority', DB::raw('count(*) as total'));
        if ($startDate && $endDate) {
            $failuresDataQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $failuresData = $failuresDataQuery->groupBy('priority')->get();
        $failuresByType = [
            'labels' => $failuresData->pluck('priority'),
            'data' => $failuresData->pluck('total'),
        ];

        // --- NOUVELLE LOGIQUE : Combinaison des Tâches, Maintenances et Activités autonomes ---

        // 1. Tâches par type
        $tasksByTypeQuery = Task::select('maintenance_type as type', DB::raw('count(*) as total'));
        if ($startDate && $endDate) $tasksByTypeQuery->whereBetween('created_at', [$startDate, $endDate]);
        $tasksByType = $tasksByTypeQuery->groupBy('type')->get();

        // 2. Maintenances par type
        $maintenancesByTypeQuery = Maintenance::select('type', DB::raw('count(*) as total'));
        if ($startDate && $endDate) $maintenancesByTypeQuery->whereBetween('created_at', [$startDate, $endDate]);
        $maintenancesByType = $maintenancesByTypeQuery->groupBy('type')->get();

        // 3. Activités autonomes (sans tâche ni maintenance)
        $standaloneActivitiesQuery = Activity::whereNull('task_id')->whereNull('maintenance_id')
            ->select(DB::raw("'Corrective' as type"), DB::raw('count(*) as total')); // On les considère comme 'Corrective' par défaut
        if ($startDate && $endDate) $standaloneActivitiesQuery->whereBetween('created_at', [$startDate, $endDate]);
        $standaloneActivities = $standaloneActivitiesQuery->groupBy('type')->get();

        // 4. Fusion des résultats
        $allInterventions = $tasksByType->concat($maintenancesByType)->concat($standaloneActivities);

        // 5. Agrégation finale
        $interventionsData = $allInterventions->groupBy('type')->map(function ($group) {
            return (object) [
                'type' => $group->first()->type,
                'total' => $group->sum('total'),
            ];
        })->values();

        $interventionsByType = [
            'labels' => $interventionsData->pluck('type'),
            'data' => $interventionsData->pluck('total'),
        ];

        // --- NOUVEAU : Taux de Maintenance Préventive ---
        $totalTasks = $interventionsData->sum('total');
        $preventiveTasks = $interventionsData->where('type', 'Préventive')->first()->total ?? 0;
        $preventiveMaintenanceRate = ($totalTasks > 0) ? ($preventiveTasks / $totalTasks) * 100 : 0;

        // --- NOUVEAU : MTBF & MTTR (Exemples de calculs) ---
        // MTTR (Mean Time To Repair) en heures
        $mttrQuery = Activity::where('status', 'completed');
        if ($startDate && $endDate) $mttrQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        $mttr = $mttrQuery->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // MTBF (Mean Time Between Failures) en jours
        // Calcul simplifié : (Période en jours) / (Nombre de pannes correctives + 1)
        $correctiveFailuresQuery = Task::where('maintenance_type', 'Corrective');
        if ($startDate && $endDate) $correctiveFailuresQuery->whereBetween('created_at', [$startDate, $endDate]);
        $correctiveFailuresCount = $correctiveFailuresQuery->count();

        $periodInDays = ($startDate && $endDate) ? $startDate->diffInDays($endDate) + 1 : 30; // fallback to 30 days
        $mtbf = ($correctiveFailuresCount > 0)
            ? $periodInDays / $correctiveFailuresCount
            : $periodInDays; // Si 0 panne, le MTBF est la période entière

        // --- NOUVEAU : Analyse du temps passé par statut de maintenance ---
        $statusDurationsQueryBase = MaintenanceStatusHistory::query();
        if ($startDate && $endDate) $statusDurationsQueryBase->whereBetween('maintenance_status_histories.created_at', [$startDate, $endDate]);

        $statusDurationsQuery = $statusDurationsQueryBase->select(
            'old_status as status',
            // Calcule la différence de temps en heures entre la création de l'historique et la création de l'historique suivant pour la même maintenance
            DB::raw("SUM(
                " . $getTimeDiffExpression(
                    '(SELECT MIN(h2.created_at) FROM maintenance_status_histories h2 WHERE h2.maintenance_id = maintenance_status_histories.maintenance_id AND h2.created_at > maintenance_status_histories.created_at)',
                    'maintenance_status_histories.created_at',
                    'min'
                ) . "
            ) as total_hours")
        )
        ->whereNotNull('old_status')
        ->groupBy('old_status');

        // Ajout des filtres optionnels pour équipement et zone
        if ($equipmentId || $zoneId) {
            $statusDurationsQuery->join('maintenances', 'maintenance_status_histories.maintenance_id', '=', 'maintenances.id')
                                 ->join('activity_maintenance', 'maintenances.id', '=', 'activity_maintenance.maintenance_id')
                                 ->join('activities', 'activity_maintenance.activity_id', '=', 'activities.id');

            if ($equipmentId) {
                $statusDurationsQuery->join('activity_equipment', 'activities.id', '=', 'activity_equipment.activity_id')
                                     ->where('activity_equipment.equipment_id', $equipmentId);
            }

            if ($zoneId) {
                $statusDurationsQuery->where('activities.zone_id', $zoneId);
            }
        }
        $statusDurations = $statusDurationsQuery->get();
        // Formattage pour le graphique
        $maintenanceStatusDurationChart = [
            'labels' => $statusDurations->pluck('status')->map(function ($status) {
                // Optionnel: rendre les noms de statuts plus lisibles
                return ucwords(str_replace('_', ' ', $status));
            }),
            'data' => $statusDurations->pluck('total_hours')->map(function ($hours) {
                // Arrondir à une décimale
                return round($hours, 1);
            }),
            'title' => "Temps moyen passé par statut (en heures)",
            'subtitle' => "Basé sur les changements de statut enregistrés dans la période."
        ];

        // --- NOUVEAU : Taux de conformité du préventif ---
     // 1. Tâches préventives planifiées
$preventiveTasksScheduledQuery = Task::where('tasks.maintenance_type', 'Préventive'); // Ajout du préfixe table

if ($startDate && $endDate) {
    $preventiveTasksScheduledQuery->whereBetween('tasks.planned_start_date', [$startDate, $endDate]);
}
$preventiveTasksScheduled = $preventiveTasksScheduledQuery->count();

// 2. Tâches préventives terminées à temps (SLA Compliance)
$preventiveTasksCompletedOnTimeQuery = Task::where('tasks.maintenance_type', 'Préventive')
    ->where('tasks.status', 'completed'); // Préfixe ajouté ici aussi

if ($startDate && $endDate) {
    $preventiveTasksCompletedOnTimeQuery->whereBetween('tasks.planned_start_date', [$startDate, $endDate]);
}

// 3. Jointure et calcul final
$preventiveTasksCompletedOnTime = $preventiveTasksCompletedOnTimeQuery
    ->join('activities', 'tasks.id', '=', 'activities.task_id')
    ->whereNotNull('activities.actual_end_time')
    // Utilisation de whereColumn pour comparer deux colonnes de tables différentes
    ->whereColumn('activities.actual_end_time', '<=', 'tasks.planned_end_date')
    ->count();

// 4. Calcul du taux avec arrondi professionnel
$preventiveComplianceRate = ($preventiveTasksScheduled > 0)
    ? round(($preventiveTasksCompletedOnTime / $preventiveTasksScheduled) * 100, 1)
    : 100;
        // --- NOUVEAU : Backlog de maintenance ---
       // Définition des statuts qui ne sont pas "finaux"
$nonFinalStatuses = ['scheduled', 'in_progress', 'awaiting_resources', 'pending'];

// On utilise now() pour comparer par rapport à l'instant présent si aucune date n'est fournie
$referenceDate = now();

// --- BACKLOG DES TÂCHES ---
$backlogTasksQuery = Task::whereIn('status', $nonFinalStatuses)
    ->where('planned_start_date', '<', $referenceDate); // Strictement inférieur = Retard

// --- BACKLOG DES MAINTENANCES ---
$backlogMaintenancesQuery = Maintenance::whereIn('status', $nonFinalStatuses)
    ->where('scheduled_start_date', '<', $referenceDate);

// --- BACKLOG DES ACTIVITÉS ---
// Activités orphelines ou spécifiques non clôturées
$backlogActivitiesQuery = Activity::whereNull('task_id')
    ->whereNull('maintenance_id')
    ->whereIn('status', $nonFinalStatuses)
    ->where('actual_start_time', '<', $referenceDate);

// --- CALCUL DES TOTAUX ---
$backlogCount = $backlogTasksQuery->count() +
                $backlogMaintenancesQuery->count() +
                $backlogActivitiesQuery->count();

// Calcul des heures de charge de travail restante (Charge du Backlog)
$backlogHours = $backlogTasksQuery->sum('estimated_duration') +
                $backlogMaintenancesQuery->sum('estimated_duration') +
                $backlogActivitiesQuery->sum(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // --- NOUVEAU : Répartition des coûts de maintenance ---
        $maintenanceCostDistribution = [
            'title' => "Répartition des Coûts de Maintenance",
            'items' => [
                [
                    'label' => 'Pièces Détachées',
                    'value' => $totalMaintenanceCost > 0 ? round(($depensesPiecesDetachees / $totalMaintenanceCost) * 100) : 0,
                    'color' => 'bg-blue-500'
                ],
                [
                    'label' => 'Main d\'œuvre',
                    'value' => $totalMaintenanceCost > 0 ? round(($laborCost / $totalMaintenanceCost) * 100) : 0,
                    'color' => 'bg-purple-500'
                ],
            ]
        ];
        // dd($expensesTotal);
        // --- NOUVEAU : Top 5 des équipements avec le plus de pannes ---
        $topFailingEquipmentsQuery = DB::table('equipment')
            ->select('equipment.designation', DB::raw('COUNT(tasks.id) as failure_count'))
            ->join('equipment_task', 'equipment.id', '=', 'equipment_task.equipment_id')
            ->join('tasks', 'equipment_task.task_id', '=', 'tasks.id')
            ->where('tasks.maintenance_type', 'Corrective')
            ->groupBy('equipment.id', 'equipment.designation');
        if ($startDate && $endDate) $topFailingEquipmentsQuery->whereBetween('tasks.created_at', [$startDate, $endDate]);
        $topFailingEquipmentsData = $topFailingEquipmentsQuery->groupBy('equipment.id', 'equipment.designation')
            ->orderByDesc('failure_count')
            ->limit(5)
            ->get();

        $topFailingEquipmentsChart = [
            'labels' => $topFailingEquipmentsData->pluck('designation'),
            'data' => $topFailingEquipmentsData->pluck('failure_count'),
            'title' => "Top 5 Équipements avec le plus de pannes",
            'subtitle' => "Basé sur les tâches correctives sur la période."
        ];

        // --- NOUVEAU : Calcul détaillé du TRS (OEE) ---

        // --- A. Calcul de la DISPONIBILITÉ (Availability) ---
        $periodInHours = ($startDate && $endDate) ? $startDate->diffInHours($endDate) : (30 * 24); // fallback 30 days
        $equipmentCountForAvailability = $equipmentId ? 1 : Equipment::count();
        $plannedProductionTime = $periodInHours * $equipmentCountForAvailability;

        // Temps d'arrêt non planifié (Correctif)
        $unplannedDowntimeQuery = Activity::join('tasks', 'activities.task_id', '=', 'tasks.id')
            ->where('tasks.maintenance_type', 'Corrective')
            ->whereNotNull('activities.actual_start_time')->whereNotNull('activities.actual_end_time');
        if ($startDate && $endDate) $unplannedDowntimeQuery->whereBetween('activities.actual_end_time', [$startDate, $endDate]);
        if ($equipmentId) { // Correction: Utiliser la bonne table pivot
            $unplannedDowntimeQuery->join('equipment_task', 'tasks.id', '=', 'equipment_task.task_id')->where('equipment_task.equipment_id', $equipmentId);
        }
        $unplannedDowntime = $unplannedDowntimeQuery->sum(DB::raw($getTimeDiffExpression('activities.actual_end_time', 'activities.actual_start_time', 'hour')));

        // Temps d'arrêt total (planifié et non planifié)
        $totalDowntimeQuery = Activity::whereNotNull('actual_start_time')->whereNotNull('actual_end_time');
        if ($startDate && $endDate) $totalDowntimeQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        if ($equipmentId) {
            $totalDowntimeQuery->join('tasks', 'activities.task_id', '=', 'tasks.id')->join('equipment_task', 'tasks.id', '=', 'equipment_task.task_id')->where('equipment_task.equipment_id', $equipmentId);
        }
        $totalDowntime = $totalDowntimeQuery->sum(DB::raw($getTimeDiffExpression('activities.actual_end_time', 'activities.actual_start_time', 'hour')));

        $runTime = $plannedProductionTime - $totalDowntime;
        $availabilityRate = ($plannedProductionTime > 0) ? (($plannedProductionTime - $unplannedDowntime) / $plannedProductionTime) : 1;

        // --- B. Calcul de la PERFORMANCE (Performance) ---
        $idealCycleRate = 0; // Cadence nominale en pièces/heure
        if ($equipmentId) {
            $idealCycleRate = (float) Equipment::find($equipmentId)->characteristics()->where('name', 'cadence_nominale')->value('value');
        }

        // On suppose que le nombre de pièces est enregistré dans une réponse d'instruction
        $totalPartsProducedQuery = InstructionAnswer::join('activity_instructions', 'instruction_answers.activity_instruction_id', '=', 'activity_instructions.id');
        if ($startDate && $endDate) $totalPartsProducedQuery->whereBetween('instruction_answers.created_at', [$startDate, $endDate]);
        $totalPartsProducedQuery->where('activity_instructions.label', 'Pièces produites');
        if ($equipmentId) {
            $totalPartsProducedQuery->whereHas('activity.equipments', fn($q) => $q->where('equipment.id', $equipmentId));
        }
        $totalPartsProduced = (int) $totalPartsProducedQuery->sum('value');

        $performanceRate = 1; // Par défaut à 100% si données non disponibles
        if ($runTime > 0 && $idealCycleRate > 0) {
            $performanceRate = ($totalPartsProduced / $runTime) / $idealCycleRate;
        }

        // --- C. Calcul de la QUALITÉ (Quality) ---
        $rejectedPartsQuery = InstructionAnswer::join('activity_instructions', 'instruction_answers.activity_instruction_id', '=', 'activity_instructions.id');
        if ($startDate && $endDate) $rejectedPartsQuery->whereBetween('instruction_answers.created_at', [$startDate, $endDate]);
        $rejectedPartsQuery->where('activity_instructions.label', 'Pièces rejetées');
        if ($equipmentId) {
            $rejectedPartsQuery->whereHas('activity.equipments', fn($q) => $q->where('equipment.id', $equipmentId));
        }
        $rejectedParts = (int) $rejectedPartsQuery->sum('value');
        $goodParts = $totalPartsProduced - $rejectedParts;
        $qualityRate = ($totalPartsProduced > 0) ? ($goodParts / $totalPartsProduced) : 1;

        // --- D. Calcul final du TRS/OEE ---
        $oee = $availabilityRate * $performanceRate * $qualityRate * 100;

        // --- NOUVEAU : Données pour les sections avec données statiques dans Vue ---
        // Work Orders (Interventions)
        $workOrders = Activity::with(['equipments', 'assignable'])
            ->whereIn('status', ['in_progress', 'scheduled', 'en cours', 'planifiée'])
            ->latest()
            ->orderBy('id')
            ->limit(10)
            ->get();

        $workOrders = $workOrders->map(function ($activity) {
                $assignable = $activity->assignable;
                $technicianName = 'Non assigné';
                $technicianImage = null;

                if ($assignable) {
                    $technicianName = $assignable->name;
                    if ($activity->assignable_type === 'App\Models\User') {
                        $technicianImage = $assignable->profile_photo_url;
                    }
                } elseif ($activity->assignable_type === 'App\Models\Team') {
                    // Assuming Team model has a 'leader' relationship or similar
                    $technicianName = $assignable->name; // Display team name
                    $technicianImage = $assignable->leader->profile_photo_url ?? null; // Display leader's photo if available
                }
                return [
                    'id' => $activity->id,
                    'asset' => $activity->equipments->first()->designation ?? $activity->title ?? 'Activité sur le réseau/ Client',
                    'location' => $activity->equipments->first()->location ?? '',
                    'priority' => $activity->task->priority ?? 'MOYENNE',
                    'technician' => $technicianName,
                    'tech_img' => $technicianImage,
                    'progress' => rand(10, 90), // Placeholder for progress
                ];
            });

        $urgentWorkOrdersCount = Activity::whereIn('status', ['in_progress', 'scheduled', 'en cours', 'planifiée', 'Planifiée'])
            ->count();

        $inProgressWorkOrdersCount = Activity::whereIn('status', ['in_progress', 'en cours', ""])->count();

        // --- NOUVEAU : Statistiques pour le flux d'interventions ---
        $awaitingWorkOrdersQuery = Activity::whereIn('status', ['awaiting_resources', 'En attente']);
        if ($startDate && $endDate) $awaitingWorkOrdersQuery->whereBetween('created_at', [$startDate, $endDate]);
        $awaitingWorkOrdersCount = $awaitingWorkOrdersQuery->count();

        $completedLast24hCount = Activity::where('status', 'completed')
            ->where('actual_end_time', '>=', now()->subHours(24))
            ->count();

        // Spare Parts (Alerte stock)
        $alertSpareParts = SparePart::whereRaw('quantity <= min_quantity')->limit(5)->get(['reference', 'quantity', 'min_quantity']);

        // Technician Efficiency
        $technicianEfficiency = User::whereHas('roles', fn($q) => $q->where('name', 'technician'))
            ->withCount(['activities as completed_tasks' => fn($q) => $q->where('status', 'completed')])
            ->withCount(['activities as backlog_tasks' => fn($q) => $q->whereIn('status', ['in_progress', 'scheduled'])])
            ->limit(5)
            ->get()
            ->map(function ($user) {
                $totalTasks = $user->completed_tasks + $user->backlog_tasks;
                return [
                    'name' => $user->name,
                    'load' => $totalTasks > 0 ? round(($user->backlog_tasks / $totalTasks) * 100) : 0,
                    'completed' => $user->completed_tasks,
                    'backlog' => $user->backlog_tasks,
                    'img' => $user->profile_photo_url,
                ];
            });

        // PREVENTIVE CALENDAR
        $calendarEvents = Maintenance::where('type', 'preventive')

            ->orderBy('scheduled_start_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                $startCarbon = $event->scheduled_start_date ? Carbon::parse($event->scheduled_start_date) : null;
                $endCarbon = $event->scheduled_end_date ? Carbon::parse($event->scheduled_end_date) : null;

                $startTime = $startCarbon ? $startCarbon->format('H:i') : '07:00';
                $endTime = $endCarbon ? $endCarbon->format('H:i') : '16:00';

                $durationInDays = 0;
                if (!empty($event->regenerated_dates)) {
                    $dates = json_decode($event->regenerated_dates, true);
                    $durationInDays = is_array($dates) ? count($dates) : 0;
                }

                $durationString = $durationInDays > 0 ? $durationInDays . ' jours (' . $startTime . ' - ' . $endTime . ')' : $startTime . ' - ' . $endTime;
                $team = $event->assignable_type === 'App\Models\Team' ? $event->assignable->name : ($event->assignable->name ?? 'Non assigné');
                return [
                    'month' => $startCarbon ? $startCarbon->format('M') : 'N/A',
                    'day' => $startCarbon ? $startCarbon->format('d') : 'N/A',
                    'title' => $event->title,
                    'duration' => $durationString,
                    'team' => $team,
                ];
            });

        // --- NOUVEAU : Interventions Récentes ---
        $recentInterventions = Activity::with(['assignable', 'equipments', 'maintenance.equipments', 'task.equipments'])
            ->where('status', 'completed');
        if ($startDate && $endDate) $recentInterventions->whereBetween('created_at', [$startDate, $endDate]);
        $recentInterventions = $recentInterventions->latest('actual_end_time')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                $technician = $activity->assignable_type === 'App\Models\User' ? $activity->assignable : null;

                $equipmentName = 'System complet'; // Valeur par défaut
                if ($activity->maintenance && $activity->maintenance->equipments->isNotEmpty()) {
                    $equipmentName = $activity->maintenance->equipments->first()->designation;
                } elseif ($activity->task && $activity->task->equipments->isNotEmpty()) {
                    $equipmentName = $activity->task->equipments->first()->designation;
                } elseif ($activity->equipments->isNotEmpty()) {
                    $equipmentName = $activity->equipments->first()->designation;
                }
                    // dd($activity->task_id);
                return [
                    'equipment' => $equipmentName,
                    'priority' => $activity->priority ?? 'Faible',
                    'technician' => $technician->name ?? 'N/A',
                    'tech_image' => $technician->profile_photo_url ?? null, // Assurez-vous que ce champ existe sur votre modèle User
                    'status' => 'Completed', // Statut fixe car on ne prend que les terminées
                ];
            });

        // --- NOUVEAU : Matrice de Risques ---
        // On prend les 2 équipements avec le plus de pannes (déjà calculé dans topFailingEquipmentsData)
        $top2FailingEquipments = $topFailingEquipmentsData->take(2);
        $riskMatrixDatasets = [];
        $riskLabels = ['Vibration', 'Chaleur', 'Cycle', 'Bruit', 'Électrique']; // Labels de risque

        foreach ($top2FailingEquipments as $index => $equipData) {
            $equipment = Equipment::where('designation', $equipData->designation)->first();
            if ($equipment) {
                $riskValues = [];
                foreach ($riskLabels as $label) {
                    // On cherche une caractéristique nommée 'risk_vibration', 'risk_chaleur', etc.
                    $riskChar = $equipment->characteristics()->where('name', 'like', 'risk_'.strtolower($label))->first();
                    $riskValues[] = $riskChar ? (int)$riskChar->value : rand(10, 40); // Valeur aléatoire si non trouvée
                }

                $colors = [
                    ['rgba(239, 68, 68, 0.2)', '#EF4444'],
                    ['rgba(99, 102, 241, 0.2)', '#6366F1']
                ];

                $riskMatrixDatasets[] = [
                    'label' => $equipment->designation,
                    'backgroundColor' => $colors[$index][0],
                    'borderColor' => $colors[$index][1],
                    'data' => $riskValues,
                ];
            }
        }

        $riskMatrixData = [
            'labels' => $riskLabels,
            'datasets' => $riskMatrixDatasets,
        ];

        // --- NOUVEAU : Calculs pour les cartes d'information sous le graphique principal ---
        // Temps moyen de clôture des activités (en heures)
        $averageClosureTimeQuery = Activity::where('status', 'completed');
        if ($startDate && $endDate) $averageClosureTimeQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        $averageClosureTime = $averageClosureTimeQuery->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // Efficacité de l'équipe (changement du nombre de tâches terminées par rapport à la période précédente)
        $completedTasksCurrentQuery = Activity::where('status', 'completed');
        if ($startDate && $endDate) $completedTasksCurrentQuery->whereBetween('actual_end_time', [$startDate, $endDate]);
        $completedTasksCurrent = $completedTasksCurrentQuery->count();
        $completedTasksPreviousQuery = Activity::where('status', 'completed');
        if ($previousStartDate && $previousEndDate) $completedTasksPreviousQuery->whereBetween('actual_end_time', [$previousStartDate, $previousEndDate]);
        $completedTasksPrevious = $completedTasksPreviousQuery->count();
        $teamEfficiencyChange = 0;
        if ($completedTasksPrevious > 0) {
            $teamEfficiencyChange = (($completedTasksCurrent - $completedTasksPrevious) / $completedTasksPrevious) * 100;
        } elseif ($completedTasksCurrent > 0) {
            $teamEfficiencyChange = 100; // Si 0 avant et > 0 maintenant, c'est une augmentation de 100%
        }

        // --- NOUVEAU : Données pour le graphique principal d'analyse de performance ---
        $mainChartData = [
            'labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'], // Exemple, à remplacer par des données réelles
            'datasets' => [
                [
                    'label' => 'Disponibilité',
                    'borderColor' => '#6366F1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'data' => [98, 97, 99, 95, 98, 99, 98], // Données statiques pour l'exemple
                ],
                [
                    'label' => 'Productivité',
                    'borderColor' => '#10B981',
                    'data' => [82, 80, 85, 84, 88, 81, 83], // Données statiques pour l'exemple
                ]
            ]
        ];
        // return $sparklineData;
        // 2. Rendu de la vue Inertia avec les props
        return Inertia::render('Dashboard', [
            // Données Basiques
            'usersCount' => $usersCount,
            'rolesCount' => $rolesCount,
            'permissionsCount' => $permissionsCount,
            'activeTasksCount' => $activeTasks,

            // Sparklines (Métriques détaillées)
            'sparklineData' => $sparklineData,

            // Filtres appliqués
            'filters' => [
                'startDate' => $startDate ? $startDate->toDateString() : null,
                'endDate' => $endDate ? $endDate->toDateString() : null,
                'equipment_id' => $equipmentId,
                'zone_id' => $zoneId,
            ],

            // Données Financières
            'budgetTotal' => $budgetTotalCalculated,
            'depensesPiecesDetachees' => $depensesPiecesDetachees,
            'depensesPrestation' => $depensesPrestation,
            'expensesTotal' => $expensesTotal,
            'totalMaintenanceCost' => $totalMaintenanceCost,
            'preventiveMaintenanceRate' => round($preventiveMaintenanceRate),
            'mttr' => round($mttr, 1),
            'mtbf' => round($mtbf, 1),
            'preventiveComplianceRate' => round($preventiveComplianceRate), // NOUVEAU
            'backlogTasksCount' => $backlogCount, // NOUVEAU
            'availabilityRate' => round(max(0, $availabilityRate * 100), 1), // On s'assure que le taux n'est pas négatif
            'oee' => round(max(0, min(100, $oee)), 1), // Le TRS est borné entre 0 et 100


            'backlogHours' => round($backlogHours / 60, 1), // NOUVEAU (en heures)

            // Graphiques
            'sparePartsMovement' => $sparePartsMovement,
            'stockRotationData' => $stockRotationData, // Ajout des données pour le graphique
            'tasksByStatus' => $tasksByStatusChart,
            'totalStockIn' => $totalStockIn, // NOUVEAU
            'totalStockOut' => $totalStockOut, // NOUVEAU
            'tasksByPriority' => $tasksByPriorityChart,
            'monthlyVolumeData' => $monthlyVolumeData,
            'failuresByType' => $failuresByType,
            'interventionsByType' => $interventionsByType,
            'maintenanceStatusDurationChart' => $maintenanceStatusDurationChart, // Ajout des données pour le nouveau graphique
            'topFailingEquipmentsChart' => $topFailingEquipmentsChart,
            'maintenanceCostDistribution' => $maintenanceCostDistribution, // NOUVEAU
            'mainChartData' => $mainChartData, // NOUVEAU

            // Données pour les sections dynamiques
            'workOrders' => $workOrders,
            'alertSpareParts' => $alertSpareParts,
            'technicianEfficiency' => $technicianEfficiency,
            'awaitingWorkOrdersCount' => $awaitingWorkOrdersCount,
            'inProgressWorkOrdersCount' => $inProgressWorkOrdersCount,
            'completedLast24hCount' => $completedLast24hCount,
            'calendarEvents' => $calendarEvents,
            'recentInterventions' => $recentInterventions, // NOUVEAU
            'riskMatrixData' => $riskMatrixData, // NOUVEAU

            'averageClosureTime' => round($averageClosureTime, 1),
            'teamEfficiencyChange' => round($teamEfficiencyChange),

            // Données pour les filtres
            'equipments' => Equipment::get(['id', 'designation']),
            'zones' => Zone::get(['id', 'title']),
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Dashboard $dashboard)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dashboard $dashboard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dashboard $dashboard)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dashboard $dashboard)
    {
        //
    }
}
