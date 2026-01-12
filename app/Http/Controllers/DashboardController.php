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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use App\Models\Equipment;
use App\Models\Expenses;

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
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        // Récupération des filtres optionnels
        $equipmentId = $request->input('equipment_id');
        $zoneId = $request->input('zone_id');

        // Période précédente (pour comparaison des métriques)
        $previousStartDate = $startDate->copy()->subMonth();
        $previousEndDate = $endDate->copy()->subMonth();

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

        // Tâches actives (planifiées dans la période, ou basées sur la date de création selon votre besoin)
        $activeTasks = Task::whereBetween('planned_start_date', [$startDate, $endDate])->count();

        // Temps moyen d'intervention (calculé directement)
        $averageInterventionTimeInMinutes = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));

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
            $dateFormat = $getDateFormat($dateColumn, '%Y-%m-%d');

            $data = $model::select(DB::raw("$dateFormat as date"), DB::raw('count(*) as count'))
                ->whereBetween($dateColumn, [$period['start'], $period['end']])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'labels' => $data->pluck('date'),
                'datasets' => [['data' => $data->pluck('count')]]
            ];
        };

        // --- Calculs pour les Sparklines ---

        // Users
        $usersCurrentCount = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $usersPreviousCount = User::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        // Tâches créées
        $activeTasksCurrentCount = Task::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeTasksPreviousCount = Task::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        // Temps Total Passé (en heures)
        $timeSpentCurrent = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->sum(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));
        $timeSpentPrevious = Activity::whereBetween('actual_end_time', [$previousStartDate, $previousEndDate])
            ->sum(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // Temps Moyen d'Intervention (en minutes)
        $avgTimeCurrent = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));
        $avgTimePrevious = Activity::whereBetween('actual_end_time', [$previousStartDate, $previousEndDate])
            ->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'minute')));

        $sparklineData = [
            // 'users' => [
            //     'value' => $usersCurrentCount,
            //     'metric' => $calculateMetric($usersCurrentCount, $usersPreviousCount),
            //     'chartData' => $generateChartData(new User, 'created_at', ['start' => $startDate, 'end' => $endDate])
            // ],
            'activeTasks' => [
                'value' => $activeTasksCurrentCount,
                'metric' => $calculateMetric($activeTasksCurrentCount, $activeTasksPreviousCount),
                'chartData' => $generateChartData(new Task, 'created_at', ['start' => $startDate, 'end' => $endDate])
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

        // --- Données Financières (Dépenses de Consommation) ---

        // Dépenses Pièces Détachées (coût estimé des pièces UTILISÉES dans les activités)
        $depensesPiecesDetachees = Activity::whereBetween('created_at', [$startDate, $endDate])
            // ->with('sparePartsUsed.sparePart')
            ->get()
            ->sum(function ($activity) {
                // Utilise unit_estimated_cost
                return $activity->sparePartsUsed->sum(function ($pivot) {
                    return $pivot->quantity_used * ($pivot->sparePart->unit_estimated_cost ?? 0);
                });
            });

        // Dépenses Prestation (Coût des ServiceOrder)
        $depensesPrestation = ServiceOrder::whereBetween('created_at', [$startDate, $endDate])->sum('cost');
// ['pending', 'approved', 'rejected', 'paid'])
        // Dépenses validées (Perte Estimée)
        $expensesTotal = Expenses::where('status', 'approved')->orWhere('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // --- Calcul du Budget Total (Dépenses d'investissement/achat) ---

        // Coût des équipements achetés sur la période
        $equipmentPurchaseCost = Equipment::whereBetween('purchase_date', [$startDate, $endDate])->sum('purchase_price');

        // Coût des pièces détachées entrées en stock sur la période
        $sparePartsInflowCost = SparePartMovement::whereBetween('spare_part_movements.created_at', [$startDate, $endDate])
            ->where('spare_part_movements.type', 'in')
            ->join('spare_parts', 'spare_part_movements.spare_part_id', '=', 'spare_parts.id')
            ->sum(DB::raw('spare_part_movements.quantity * spare_parts.price'));

        $budgetTotalCalculated = $equipmentPurchaseCost + $sparePartsInflowCost + $depensesPrestation;

        // --- Coûts de Maintenance ---
        $laborCost = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->sum(DB::raw($getTimeDiffExpression('activities.actual_end_time', 'activities.actual_start_time', 'hour') . ' * COALESCE(users.hourly_rate, 0)'));

        $totalMaintenanceCost = $depensesPiecesDetachees + $depensesPrestation + $expensesTotal + $laborCost;



        // --- Mouvements de Pièces Détachées ---
        $movements = SparePartMovement::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(CASE WHEN type = 'entree' THEN quantity ELSE 0 END) as entries"),
                DB::raw("SUM(CASE WHEN type = 'sortie' THEN quantity ELSE 0 END) as exits")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $sparePartsMovement = [
            'labels' => $movements->pluck('date'),
            'entries' => $movements->pluck('entries'),
            'exits' => $movements->pluck('exits'),
        ];


        // --- Tâches par Statut et Priorité (Graphiques en secteurs/barres) ---

        $tasksByStatus = Task::select('status', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')->get()->pluck('total', 'status')->toArray();
        $tasksByStatusChart = [
            'labels' => array_keys($tasksByStatus),
            'data' => array_values($tasksByStatus),
        ];

        $tasksByPriority = Task::select('priority', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('priority')->get()->pluck('total', 'priority')->toArray();
        $tasksByPriorityChart = [
            'labels' => array_keys($tasksByPriority),
            'data' => array_values($tasksByPriority),
        ];

        // --- Volume Mensuel & Temps de Résolution Moyen (Graphique combiné) ---

        // **CORRECTION de l'ambiguïté :** On spécifie la table 'tasks' pour la première requête (volume)
        $monthlyTaskFormat = $getDateFormat('tasks.created_at', '%Y-%m');

        $monthlyData = Task::whereBetween('created_at', [$startDate, $endDate])
            ->select(
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

        $resolutionData = Activity::join('tasks', 'activities.task_id', '=', 'tasks.id')
            ->whereBetween('activities.created_at', [$startDate, $endDate])
            ->select(
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
        $failuresData = Task::where('maintenance_type', 'Corrective')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')->get();
        $failuresByType = [
            'labels' => $failuresData->pluck('priority'),
            'data' => $failuresData->pluck('total'),
        ];

        $interventionsData = Task::whereBetween('created_at', [$startDate, $endDate])
            ->select('maintenance_type', DB::raw('count(*) as total'))
            ->groupBy('maintenance_type')->get();
        $interventionsByType = [
            'labels' => $interventionsData->pluck('maintenance_type'),
            'data' => $interventionsData->pluck('total'),
        ];

        // --- NOUVEAU : Taux de Maintenance Préventive ---
        $totalTasks = $interventionsData->sum('total');
        $preventiveTasks = $interventionsData->where('maintenance_type', 'Préventive')->first()->total ?? 0;
        $preventiveMaintenanceRate = ($totalTasks > 0) ? ($preventiveTasks / $totalTasks) * 100 : 0;

        // --- NOUVEAU : MTBF & MTTR (Exemples de calculs) ---
        // MTTR (Mean Time To Repair) en heures
        $mttr = Activity::where('status', 'completed')
            ->whereBetween('actual_end_time', [$startDate, $endDate])
            ->avg(DB::raw($getTimeDiffExpression('actual_end_time', 'actual_start_time', 'hour')));

        // MTBF (Mean Time Between Failures) en jours
        // Calcul simplifié : (Période en jours) / (Nombre de pannes correctives + 1)
        $correctiveFailuresCount = Task::where('maintenance_type', 'Corrective')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $periodInDays = $startDate->diffInDays($endDate) + 1;
        $mtbf = ($correctiveFailuresCount > 0)
            ? $periodInDays / $correctiveFailuresCount
            : $periodInDays; // Si 0 panne, le MTBF est la période entière

        // --- NOUVEAU : Analyse du temps passé par statut de maintenance ---
        $statusDurationsQuery = MaintenanceStatusHistory::select(
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
        ->whereBetween('maintenance_status_histories.created_at', [$startDate, $endDate])
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
        $preventiveTasksScheduled = Task::where('maintenance_type', 'Préventive')
            ->whereBetween('planned_start_date', [$startDate, $endDate])
            ->count();
        $preventiveTasksCompletedOnTime = Task::where('maintenance_type', 'Préventive')
            ->where('status', 'completed')
            ->whereBetween('planned_start_date', [$startDate, $endDate])
            // ->whereNotNull('actual_end_time')
            // ->whereRaw('actual_end_time <= planned_end_date')
            ->count();
        $preventiveComplianceRate = ($preventiveTasksScheduled > 0) ? ($preventiveTasksCompletedOnTime / $preventiveTasksScheduled) * 100 : 100;

        // --- NOUVEAU : Backlog de maintenance ---
        $backlogTasks = Task::whereIn('status', ['scheduled', 'in_progress', 'awaiting_resources'])
            ->where('planned_start_date', '<=', $endDate);
        $backlogTasksCount = $backlogTasks->count();
        $backlogHours = $backlogTasks->sum('estimated_duration');

        // --- NOUVEAU : Répartition des coûts de maintenance ---
        $maintenanceCostDistribution = [
            'labels' => ['Main d\'œuvre', 'Pièces Détachées', 'Prestations Externes', 'Autres Dépenses'],
            'data' => [$laborCost, $depensesPiecesDetachees, $depensesPrestation, $expensesTotal],
            'title' => "Répartition des Coûts de Maintenance",
        ];

        // --- NOUVEAU : Top 5 des équipements avec le plus de pannes ---
        $topFailingEquipmentsData = Equipment::select('equipment.designation', DB::raw('COUNT(tasks.id) as failure_count'))
            ->join('activity_equipment', 'equipment.id', '=', 'activity_equipment.equipment_id')
            ->join('activities', 'activity_equipment.activity_id', '=', 'activities.id')
            ->join('tasks', 'activities.task_id', '=', 'tasks.id')
            ->where('tasks.maintenance_type', 'Corrective')
            ->whereBetween('tasks.created_at', [$startDate, $endDate])
            ->groupBy('equipment.id', 'equipment.designation')
            ->orderByDesc('failure_count')
            ->limit(5)
            ->get();

        $topFailingEquipmentsChart = [
            'labels' => $topFailingEquipmentsData->pluck('designation'),
            'data' => $topFailingEquipmentsData->pluck('failure_count'),
            'title' => "Top 5 Équipements avec le plus de pannes",
            'subtitle' => "Basé sur les tâches correctives sur la période."
        ];



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
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
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
            'backlogTasksCount' => $backlogTasksCount, // NOUVEAU
            'backlogHours' => round($backlogHours / 60, 1), // NOUVEAU (en heures)

            // Graphiques
            'sparePartsMovement' => $sparePartsMovement,
            'tasksByStatus' => $tasksByStatusChart,
            'tasksByPriority' => $tasksByPriorityChart,
            'monthlyVolumeData' => $monthlyVolumeData,
            'failuresByType' => $failuresByType,
            'interventionsByType' => $interventionsByType,
            'maintenanceStatusDurationChart' => $maintenanceStatusDurationChart, // Ajout des données pour le nouveau graphique
            'topFailingEquipmentsChart' => $topFailingEquipmentsChart,
            'maintenanceCostDistribution' => $maintenanceCostDistribution, // NOUVEAU

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
