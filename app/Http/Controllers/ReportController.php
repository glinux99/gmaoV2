<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Report::query()->with('user:id,name');

        // --- FILTRES ---
        $query->when($request->report_type && $request->report_type !== 'all', fn($q) => $q->where('report_type', $request->report_type))
              ->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status));

        // --- RECHERCHE ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        return Inertia::render('Reports', [
            'reports' => $query->latest()->paginate($request->input('per_page', 10)),
            'filters' => $request->all(['search', 'report_type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Reports/Create', [
            'reportTypes' => ['daily', 'weekly', 'monthly', 'custom'], // Example types
            'statuses' => ['generated', 'pending', 'failed'], // Example statuses
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|string|in:daily,weekly,monthly,custom',
            'parameters' => 'nullable|array',
            'status' => 'nullable|string|in:generated,pending,failed',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'pending';

        Report::create($validated);

        return redirect()->route('reports.index')->with('success', 'Rapport créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        return Inertia::render('Reports/Show', [
            'report' => $report->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Report $report)
    {
        return Inertia::render('Reports/Edit', [
            'report' => $report->load('user'),
            'reportTypes' => ['daily', 'weekly', 'monthly', 'custom'],
            'statuses' => ['generated', 'pending', 'failed'],
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|string|in:daily,weekly,monthly,custom',
            'parameters' => 'nullable|array',
            'status' => 'required|string|in:generated,pending,failed',
            'scheduled_at' => 'nullable|date',
            'file_path' => 'nullable|string', // Allow updating file path if report is generated
            'generated_at' => 'nullable|date', // Allow updating generated_at
        ]);

        $report->update($validated);

        return redirect()->route('reports.index')->with('success', 'Rapport mis à jour avec succès.');
    }

   public function getModels()
    {
        $modelPath = app_path('Models');
        $files = File::files($modelPath);

        $models = collect($files)->map(function ($file) {
            $name = $file->getFilenameWithoutExtension();
            return [
                'id'   => "App\\Models\\$name",
                'name' => $name
            ];
        });

        return response()->json($models);
    }

    /**
     * Mission 2 : Extraire les données selon la config du widget (POST)
     * Route: Route::post('/api/quantum/query', [QuantumReportController::class, 'fetchData']);
     */
    public function fetchData(Request $request)
    {
        $request->validate([
            'model'   => 'required|string',
            'column'  => 'required|string', // ex: 'price' ou 'id'
            'method'  => 'required|in:SUM,AVG,COUNT,MAX',
        ]);

        $modelClass = $request->input('model');

        // Vérification de sécurité pour s'assurer que la classe existe
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            return response()->json(['error' => 'Modèle introuvable'], 404);
        }

        // Calcul de la valeur KPI
        $query = $modelClass::query();
        $method = strtolower($request->input('method'));
        $value = $query->$method($request->input('column'));

        // Génération formatée pour Chart.js (Exemple : Groupé par mois)
        $chartData = $this->generateChartData($modelClass, $request->input('column'), $method);

        return response()->json([
            'value'     => number_format($value, 2, '.', ' '),
            'chart'     => $chartData,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    private function generateChartData($modelClass, $column, $method)
    {
        // Exemple simple : on groupe les données des 6 derniers mois
        $data = DB::table((new $modelClass)->getTable())
            ->select(DB::raw('MONTHNAME(created_at) as label'), DB::raw("$method($column) as value"))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('label')
            ->get();

        return [
            'labels' => $data->pluck('label'),
            'datasets' => [[
                'label' => "Analyse $method",
                'data'  => $data->pluck('value'),
                'backgroundColor' => '#6366f1'
            ]]
        ];
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('reports.index')->with('success', 'Rapport supprimé avec succès.');
    }

    public function reorder(Request $request)
    {
        foreach ($request->orders as $item) {
            Report::where('id', $item['id'])->update(['order' => $item['order']]);
        }
        return back();
    }
}
