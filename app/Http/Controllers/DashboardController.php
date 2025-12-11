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
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Gestion de la période de filtrage
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth();

        // Période précédente pour le calcul des métriques (ex: "from last month")
        $previousStartDate = $startDate->copy()->subMonth();
        $previousEndDate = $endDate->copy()->subMonth();

        // Basic data
        $usersCount = User::count();
        $rolesCount = Role::count();
        $permissionsCount = Permission::count();

        // Number of active tasks
        $activeTasks = Task::whereBetween('planned_start_date', [$startDate, $endDate])->count();
        $timeSpent = "120h";

        $timeSpentInMinutes = Activity::whereBetween('activities.actual_end_time', [$startDate, $endDate])
            ->join('tasks', 'activities.task_id', '=', 'tasks.id')
            ->select(DB::raw('AVG(ABS(CAST(strftime(\'%s\', activities.actual_end_time) - strftime(\'%s\', activities.actual_start_time) AS REAL) / 60 - (CAST(strftime(\'%s\', tasks.planned_end_date) - strftime(\'%s\', tasks.planned_start_date) AS REAL) / 60))) as avg_time_difference'))
            ->value('avg_time_difference');

        $timeSpent = $timeSpentInMinutes ?
            round($timeSpentInMinutes) . 'm' :
            '0m';
        $averageInterventionTimeInMinutes = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->avg(DB::raw('CAST(strftime(\'%s\', activities.actual_end_time) - strftime(\'%s\', activities.actual_start_time) AS REAL) / 60'));

        $averageInterventionTime = $averageInterventionTimeInMinutes ?
            round($averageInterventionTimeInMinutes) . 'm' :
            '0m';

        // --- Helper function to calculate percentage change ---
        $calculateMetric = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? '+100%' : '0%';
            }
            $change = (($current - $previous) / $previous) * 100;
            return ($change >= 0 ? '+' : '') . round($change) . '%';
        };

        // --- Helper function to generate chart data for a period ---
        $generateChartData = function ($model, $dateColumn, $period) {
            $data = $model::select(DB::raw("DATE($dateColumn) as date"), DB::raw('count(*) as count'))
                ->whereBetween($dateColumn, [$period['start'], $period['end']])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'labels' => $data->pluck('date'),
                'datasets' => [['data' => $data->pluck('count')]]
            ];
        };

        // --- Calculate metrics and chart data for Sparklines ---
        $usersCurrentCount = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $usersPreviousCount = User::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        $activeTasksCurrentCount = Task::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeTasksPreviousCount = Task::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        $timeSpentCurrent = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->sum(DB::raw('CAST(strftime(\'%s\', actual_end_time) - strftime(\'%s\', actual_start_time) AS REAL) / 3600')); // in hours
        $timeSpentPrevious = Activity::whereBetween('actual_end_time', [$previousStartDate, $previousEndDate])
            ->sum(DB::raw('CAST(strftime(\'%s\', actual_end_time) - strftime(\'%s\', actual_start_time) AS REAL) / 3600'));

        $avgTimeCurrent = Activity::whereBetween('actual_end_time', [$startDate, $endDate])
            ->avg(DB::raw('CAST(strftime(\'%s\', actual_end_time) - strftime(\'%s\', actual_start_time) AS REAL) / 60')); // in minutes
        $avgTimePrevious = Activity::whereBetween('actual_end_time', [$previousStartDate, $previousEndDate])
            ->avg(DB::raw('CAST(strftime(\'%s\', actual_end_time) - strftime(\'%s\', actual_start_time) AS REAL) / 60'));


        $sparklineData = [
            'users' => [
                'metric' => $calculateMetric($usersCurrentCount, $usersPreviousCount),
                'chartData' => $generateChartData(new User, 'created_at', ['start' => $startDate, 'end' => $endDate])
            ],
            'activeTasks' => [
                'metric' => $calculateMetric($activeTasksCurrentCount, $activeTasksPreviousCount),
                'chartData' => $generateChartData(new Task, 'created_at', ['start' => $startDate, 'end' => $endDate])
            ],
            'timeSpent' => [
                'metric' => $calculateMetric($timeSpentCurrent, $timeSpentPrevious),
                'chartData' => $generateChartData(new Activity, 'actual_end_time', ['start' => $startDate, 'end' => $endDate])
            ],
            'averageInterventionTime' => [
                'metric' => $calculateMetric($avgTimeCurrent, $avgTimePrevious),
                'chartData' => $generateChartData(new Activity, 'actual_end_time', ['start' => $startDate, 'end' => $endDate])
            ],
        ];
        // --- Données pour les graphiques Sparkline ---
        // NOTE: Ces données sont des exemples. Vous devrez implémenter la logique pour récupérer
        // les données sur la période et calculer la métrique par rapport à la période précédente.
        $sparklineData = [
            'users' => [
                'metric' => '+5%',
                'chartData' => ['labels' => ['Jan', 'Fev', 'Mar'], 'datasets' => [['data' => [10, 15, 12]]]]
            ],
            'activeTasks' => [
                'metric' => '-10%',
                'chartData' => ['labels' => ['Jan', 'Fev', 'Mar'], 'datasets' => [['data' => [20, 18, 25]]]]
            ],
            'timeSpent' => [
                'metric' => '+8%',
                'chartData' => ['labels' => ['Jan', 'Fev', 'Mar'], 'datasets' => [['data' => [100, 120, 110]]]]
            ],
            'averageInterventionTime' => [
                'metric' => '+2%',
                'chartData' => ['labels' => ['Jan', 'Fev', 'Mar'], 'datasets' => [['data' => [40, 45, 42]]]]
            ],
        ];

        // Financial data
        // Expenses for spare parts used in tasks and activities
        $depensesPiecesDetachees = Activity::whereBetween('created_at', [$startDate, $endDate])
            ->with('sparePartActivities.sparePart')
            ->get()
            ->sum(function ($activity) { // Changed from sparePartsUsed to sparePartActivities
                return $activity->sparePartActivities->sum(function ($pivot) {
                    return $pivot->quantity_used * ($pivot->sparePart->unit_estimated_cost ?? 0);
                });
            });

        // Calculate the estimated cost of services performed
        $depensesPrestation = ServiceOrder::whereBetween('created_at', [$startDate, $endDate])->sum('cost');

        // Calculate estimated loss (you might need a Downtime model)
        $perteEstimee = 580;

        // Spare part movements
        $movements = SparePartMovement::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as entries"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as exits")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $sparePartsMovement = [
            'labels' => $movements->pluck('date'),
            'entries' => $movements->pluck('entries'),
            'exits' => $movements->pluck('exits'),
        ];

        // Tasks by status
        $tasksByStatus = [
            'labels' => [],
            'data' => [],
        ];

        $tasksStatusData = Task::select('status', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $tasksByStatus['labels'] = array_keys($tasksStatusData);
        $tasksByStatus['data'] = array_values($tasksStatusData);

        // Tasks by priority
        $tasksByPriority = [
            'labels' => [],
            'data' => [],
        ];

        $tasksPriorityData = Task::select('priority', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('priority')
            ->get()
            ->pluck('total', 'priority')
            ->toArray();

        $tasksByPriority['labels'] = array_keys($tasksPriorityData);
        $tasksByPriority['data'] = array_values($tasksPriorityData);

        // Monthly volume
        $monthlyData = Task::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw("SUM(CASE WHEN maintenance_type = 'Corrective' THEN 1 ELSE 0 END) as stopped"), // Assuming Corrective is 'stopped'
                DB::raw("SUM(CASE WHEN maintenance_type = 'Préventive' THEN 1 ELSE 0 END) as degraded"), // Assuming Preventive is 'degraded'
                DB::raw("SUM(CASE WHEN maintenance_type = 'Améliorative' THEN 1 ELSE 0 END) as improvement")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $resolutionData = Activity::join('tasks', 'activities.task_id', '=', 'tasks.id')
            ->whereBetween('activities.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("strftime('%Y-%m', activities.created_at) as month"),
                DB::raw("AVG(CAST(strftime('%s', activities.actual_end_time) - strftime('%s', activities.actual_start_time) AS REAL) / 3600) as avg_resolution_hours")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()->pluck('avg_resolution_hours', 'month');

        $monthlyVolumeData = [
            'labels' => $monthlyData->pluck('month'),
            'stopped' => $monthlyData->pluck('stopped'),
            'degraded' => $monthlyData->pluck('degraded'),
            'improvement' => $monthlyData->pluck('improvement'),
            'resolutionTime' => $monthlyData->pluck('month')->map(fn ($month) => $resolutionData->get($month) ?? 0),
        ];

        // Failures by type of defect
        $failuresData = Task::where('maintenance_type', 'Corrective')->whereBetween('created_at', [$startDate, $endDate])->select('priority', DB::raw('count(*) as total'))->groupBy('priority')->get();
        $failuresByType = [
            'labels' => $failuresData->pluck('priority'),
            'data' => $failuresData->pluck('total'),
        ];

        // Interventions par type (exemple)
        $interventionsData = Task::whereBetween('created_at', [$startDate, $endDate])->select('maintenance_type', DB::raw('count(*) as total'))->groupBy('maintenance_type')->get();
        $interventionsByType = [
            'labels' => $interventionsData->pluck('maintenance_type'),
            'data' => $interventionsData->pluck('total'),
        ];

        // 2. Rendu de la vue Inertia avec les props
        return Inertia::render('Dashboard', [
            'users' => $usersCount,
            'roles' => $rolesCount,
            'permissions' => $permissionsCount,
            'activeTasks' => $activeTasks,
            'timeSpent' => $timeSpent,
            'averageInterventionTime' => $averageInterventionTime,
            'sparklineData' => $sparklineData,
            'filters' => [
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ],
             'depensesPiecesDetachees' => $depensesPiecesDetachees,
            'depensesPrestation' => $depensesPrestation,
            'perteEstimee' => $perteEstimee,
            'sparePartsMovement' => $sparePartsMovement,
            'tasksByStatus' => $tasksByStatus,
            'tasksByPriority' => $tasksByPriority,
            'depensesPiecesDetachees' => $depensesPiecesDetachees,
            'depensesPrestation' => $depensesPrestation,
            'perteEstimee' => $perteEstimee,
            'monthlyVolumeData' => $monthlyVolumeData,
            'failuresByType' => $failuresByType,
            'interventionsByType' => $interventionsByType,
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
