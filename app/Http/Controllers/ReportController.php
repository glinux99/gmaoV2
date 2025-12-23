<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, File, Schema};
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Synchronise et liste les modèles disponibles ainsi que leurs colonnes.
     */
    public function getModels()
    {
        try {
            $modelPath = app_path('Models');
            if (!File::isDirectory($modelPath)) return response()->json([]);

            $files = File::files($modelPath);

            $models = collect($files)->map(function ($file) {
                $name = $file->getFilenameWithoutExtension();
                $className = "App\\Models\\$name";

                if (class_exists($className)) {
                    $model = new $className;
                    return [
                        'id'      => $name, // On garde le nom court pour faciliter le mapping front
                        'full_id' => $className,
                        'name'    => $name,
                        'columns' => Schema::getColumnListing($model->getTable()),
                    ];
                }
                return null;
            })->filter()->values();

            return response()->json($models);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Point d'entrée unique pour l'extraction des données (Charts & KPIs).
     */
    public function fetchData(Request $request)
    {
        $validated = $request->validate([
            'type'   => 'required|string|in:chart,kpi',
            'config' => 'required|array',
            // Validation commune
            'config.timeScale' => 'required|string',
            // Validation spécifique
            'config.sources' => 'required_if:type,chart|array',
            'config.model'   => 'required_if:type,kpi|string',
            'config.column'  => 'required_if:type,kpi|string',
            'config.method'  => 'required_if:type,kpi|string|in:COUNT,SUM,AVG,MAX,MIN',
        ]);

        if ($validated['type'] === 'chart') {
            return $this->fetchChartData($validated['config']);
        }

        return $this->fetchKPIData($validated['config']);
    }

    /**
     * Logique spécifique pour les widgets KPI (Valeur unique)
     */
    private function fetchKPIData($config)
    {
        $modelClass = "App\\Models\\" . $config['model'];
        $column = $config['column'];
        $method = strtolower($config['method']);
        $timeScale = $config['timeScale'];

        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Modèle introuvable'], 404);
        }

        $dateRange = $this->getDateRange($timeScale);

        $value = $modelClass::query()
            ->where('created_at', '>=', $dateRange)
            ->$method($column);

        return response()->json([
            'value' => is_numeric($value) ? number_format($value, 2, '.', '') : $value,
            'label' => strtoupper($method) . "($column)"
        ]);
    }

    /**
     * Logique spécifique pour les Graphiques (Séries temporelles)
     */
    private function fetchChartData($config)
    {
        $timeScale = $config['timeScale'];
        $dateFormat = $this->getDateFormat($timeScale);
        $dateRange = $this->getDateRange($timeScale);

        $allLabels = collect();
        $datasets = [];

        foreach ($config['sources'] as $source) {
            $modelClass = "App\\Models\\" . $source['model'];
            $column = $source['column'];
            $color = $source['color'] ?? '#6366F1';

            if (!class_exists($modelClass)) continue;

            $model = new $modelClass;
            $results = DB::table($model->getTable())
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '$dateFormat') as label"),
                    DB::raw("COUNT($column) as total")
                )
                ->where('created_at', '>=', $dateRange)
                ->groupBy('label')
                ->orderBy(DB::raw("MIN(created_at)"), 'ASC')
                ->pluck('total', 'label');

            // Fusionner les labels pour avoir un axe X commun
            $allLabels = $allLabels->merge($results->keys())->unique()->sort();

            $datasets[] = [
                'label'           => "{$source['model']} ({$column})",
                'data'            => $results, // On stocke temporairement l'objet complet
                'original_results'=> $results,
                'borderColor'     => $color,
                'backgroundColor' => $color . '33',
                'borderWidth'     => 2,
                'tension'         => 0.4,
                'fill'            => true,
            ];
        }

        // Réaligner les données de chaque dataset sur les labels globaux (remplir par 0 si vide)
        $formattedDatasets = collect($datasets)->map(function ($ds) use ($allLabels) {
            $alignedData = $allLabels->map(fn($l) => $ds['original_results']->get($l, 0))->values();
            unset($ds['original_results']); // Nettoyage
            $ds['data'] = $alignedData;
            return $ds;
        });

        return response()->json([
            'labels'   => $allLabels->values(),
            'datasets' => $formattedDatasets,
        ]);
    }

    // --- HELPERS TEMPORELS ---

    private function getDateFormat($timeScale) {
        return match ($timeScale) {
            'minutes' => '%H:%i',
            'hours'   => '%H:00',
            'days'    => '%d %b',
            'weeks'   => 'Sem %u',
            'months'  => '%b %Y',
            default   => '%Y-%m-%d',
        };
    }

    private function getDateRange($timeScale) {
        return match ($timeScale) {
            'minutes' => now()->subHours(1),
            'hours'   => now()->subHours(24),
            'days'    => now()->subDays(30),
            'weeks'   => now()->subWeeks(12),
            'months'  => now()->subMonths(12),
            default   => now()->subDays(30),
        };
    }

    // --- CRUD MÉTHODES ---

    public function index(Request $request)
    {
        $query = Report::query()->with('user:id,name');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('Reports', [
            'reports' => $query->latest()->paginate($request->input('per_page', 10)),
            'filters' => $request->all(['search', 'report_type', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'report_type'  => 'required|string|in:daily,weekly,monthly,custom',
            'parameters'   => 'nullable|array',
            'status'       => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        Report::create($validated);

        return redirect()->route('reports.index')->with('success', 'Rapport Quantum enregistré.');
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return back()->with('success', 'Supprimé.');
    }
}
