<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use App\Models\User;
// Assurez-vous d'importer les modèles 'expensable' nécessaires
use App\Models\Activity;
use App\Models\Maintenance;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource with custom grouping.
     */
    public function index()
    {
        // 1. Charger les dépenses avec les relations nécessaires
        $query = Expenses::with(['user', 'approvedBy']);

        // Application du filtre de recherche (si présent)
        if (request()->has('search')) {
            $search = request('search');
            $query->where('description', 'like', '%' . $search . '%')
                ->orWhere('category', 'like', '%' . $search . '%')
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        $expenses = $query->get();

        // 2. Regrouper les dépenses selon les critères spécifiés
        $groupedExpenses = $expenses->groupBy(function ($expense) {
            // Clé de regroupement : category-user_id-expensable_type-expensable_id
            return $expense->category . '-' . $expense->user_id . '-' . $expense->expensable_type . '-' . $expense->expensable_id;
        })->map(function ($group) {
            $firstExpense = $group->first(); // Le premier élément pour récupérer les infos de base
            $totalCost = 0;
            $allDescriptions = [];
            $senders = [];
            $expensableTitle = 'N/A';

            // Tenter de déterminer le titre de l'entité associée (expensable)
            if ($firstExpense->expensable_type && $firstExpense->expensable_id) {
                switch ($firstExpense->expensable_type) {
                    case Activity::class:
                        $expensable = Activity::find($firstExpense->expensable_id);
                        $expensableTitle = $expensable ? 'Activité: ' . ($expensable->task ? $expensable->task->title : 'N/A') : 'N/A';
                        break;
                    case Maintenance::class:
                        $expensable = Maintenance::find($firstExpense->expensable_id);
                        $expensableTitle = $expensable ? 'Maintenance: ' . $expensable->title : 'N/A';
                        break;
                    case ServiceOrder::class:
                        $expensable = ServiceOrder::find($firstExpense->expensable_id);
                        $expensableTitle = $expensable ? 'Prestation: ' . $expensable->description : 'N/A';
                        break;
                        // Ajouter d'autres cas si nécessaire
                }
            }

            // 3. Agrégation: sommer les coûts, collecter les descriptions et les utilisateurs
            foreach ($group as $expense) {
                $totalCost += $expense->amount;
                $allDescriptions[] = $expense->description;
                if ($expense->user) {
                    $senders[$expense->user->id] = $expense->user->name;
                }
            }

            // 4. Retourner l'objet regroupé et agrégé
            return [
                'category' => $firstExpense->category,
                'user_id' => $firstExpense->user_id,
                'user_name' => $firstExpense->user ? $firstExpense->user->name : 'N/A',
                'expensable_type' => $firstExpense->expensable_type,
                'expensable_id' => $firstExpense->expensable_id,
                'expensable_title' => $expensableTitle, // Le titre de l'entité parente
                'consolidated_description' => implode('; ', $allDescriptions), // Toutes les descriptions concaténées
                'total_cost' => $totalCost, // Coût total
                'senders' => array_values($senders), // Noms des utilisateurs uniques
                'details' => $group->toArray(), // Conserver les détails des dépenses individuelles si besoin
            ];
        })->values(); // Réinitialiser les clés numériques

        return Inertia::render('Tasks/Expenses', [
            'expenses' => $groupedExpenses, // Passer les dépenses regroupées
            'filters' => request()->only(['search']),
            'users' => User::all(['id', 'name']),
        ]);
    }

    // ----------------------------------------------------
    // Autres méthodes CRUD (aucune modification)
    // ----------------------------------------------------

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Tasks/Expenses', [
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category' => 'required|string|in:parts,labor,travel,external_service,other',
            'user_id' => 'nullable|exists:users,id',
            'expensable_type' => 'nullable|string',
            'expensable_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'receipt_path' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected,paid',
            'approved_by' => 'nullable|exists:users,id',
            'approval_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ], ['expensable_type.required_with' => 'The expensable type is required when expensable ID is present.']);

        $validated['user_id'] = $validated['user_id'] ?? Auth::id();

        Expenses::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Dépense créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expenses $expense)
    {
        return Inertia::render('Tasks/Expenses/Show', [
            'expense' => $expense->load(['user', 'approvedBy']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expenses $expense)
    {
        return Inertia::render('Tasks/Expenses', [
            'expense' => $expense->load(['user', 'approvedBy']),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expenses $expense)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'expense_date' => 'nullable|date',
            'category' => 'nullable|string|in:parts,labor,travel,external_service,other',
            'user_id' => 'nullable|exists:users,id',
            'expensable_type' => 'nullable|string',
            'expensable_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'receipt_path' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected,paid',
            'approved_by' => 'nullable|exists:users,id', // Should be nullable as it's set by updateStatus
            'approval_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expenses $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Dépense supprimée avec succès.');
    }

    /**
     * Update the status of the specified expense.
     */
    public function updateStatus(Request $request, Expenses $expense)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected,paid',
        ]);

        $expense->update([
            'status' => $validated['status'],
            'approved_by' => Auth::id(), // Set approved_by to the currently authenticated user
            'approval_date' => now(),    // Set approval_date to the current timestamp
        ]);

        return redirect()->route('expenses.index')->with('success', 'Statut de la dépense mis à jour avec succès.');
    }

    /**
     * Update the status of a group of expenses.
     */
    public function updateGroupStatus(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array',
            'expense_ids.*' => 'exists:expenses,id',
            'status' => 'required|string|in:pending,approved,rejected,paid',
        ]);

        Expenses::whereIn('id', $validated['expense_ids'])->update([
            'status' => $validated['status'],
            'approved_by' => Auth::id(), // Set approved_by to the currently authenticated user
            'approval_date' => now(),    // Set approval_date to the current timestamp
        ]);

        return redirect()->route('expenses.index')->with('success', 'Statut des dépenses sélectionnées mis à jour avec succès.');
    }
}
