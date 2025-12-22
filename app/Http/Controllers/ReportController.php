<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('reports.index')->with('success', 'Rapport supprimé avec succès.');
    }
}
