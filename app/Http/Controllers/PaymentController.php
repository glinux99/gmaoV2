<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Payment::with(['paidBy', 'payable'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('payment_date', [$startDate, $endDate]);
            });

        if ($search = $request->input('search')) {
            // ... (logique de recherche inchangée)
            $query->where('reference', 'like', "%{$search}%")
                ->orWhere('payment_method', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhereHas('paidBy', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHasMorph('payable', [Employee::class], fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                ->orWhereHasMorph('payable', [Supplier::class], fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        // --- Préparation des données pour les Dropdowns polymorphiques ---

        // Employés: Mappage pour créer le champ 'name' combiné (prénom + nom)
        $employeesList = Employee::all(['id', 'first_name', 'last_name']);
        $employees = $employeesList->map(fn ($e) => ['id' => $e->id, 'name' => $e->first_name . ' ' . $e->last_name])->toArray();

        // Fournisseurs
        $suppliers = Supplier::all(['id', 'name']);

        // Utilisateurs
        $users = User::all(['id', 'name']);


        // --- Construction de la prop 'payables' agrégée pour l'affichage de la DataTable ---

        // 1. Ajouter les Employés
        $payablesAggregated = $employeesList->map(function ($employee) {
            return [
                'id' => $employee->id,
                'type' => Employee::class,
                'name' => $employee->first_name . ' ' . $employee->last_name,
            ];
        });

        // 2. Ajouter les Fournisseurs
        $payablesAggregated = $payablesAggregated->concat(
            $suppliers->map(fn ($supplier) => [
                'id' => $supplier->id,
                'type' => Supplier::class,
                'name' => $supplier->name
            ])
        );

        // 3. Ajouter les Utilisateurs
        $payablesAggregated = $payablesAggregated->concat(
            $users->map(fn ($user) => [
                'id' => $user->id,
                'type' => User::class,
                'name' => $user->name
            ])
        );

        // --- Rendu de la vue Inertia ---

        return Inertia::render('HumanResources/Payment', [
            'payments' => $query->paginate(10),
            'filters' => $request->only('search'),

            // PROP AGRÉGÉE POUR LA DATATABLE (résolution du nom)
            'payables' => $payablesAggregated->toArray(),

            // PROPS DÉTAILLÉES POUR LES DROPDOWNS DU FORMULAIRE
            'users' => $users->toArray(),
            'employees' => $employees,
            'suppliers' => $suppliers->toArray(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Finance/Payments', [
            'users' => User::all(['id', 'name']),
            'employees' => Employee::all(['id', 'first_name', 'last_name']),
            'suppliers' => Supplier::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:bank_transfer,cash,check,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:pending,completed,failed,refunded',
            'paid_by' => 'nullable|exists:users,id',
            'payable_type' => 'nullable|string|in:App\\Models\\Employee,App\\Models\\Supplier,App\\Models\\User',
            'payable_id' => 'nullable|integer',
            'category' => 'required|string|max:255',
        ]);

        $validated['paid_by'] = $validated['paid_by'] ?? Auth::id();

        Payment::create($validated);

        return redirect()->route('payroll.index')->with('success', 'Paiement créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return Inertia::render('Finance/Payments/Show', [
            'payment' => $payment->load(['paidBy', 'payable']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        return Inertia::render('Finance/Payments', [
            'payment' => $payment->load(['paidBy', 'payable']),
            'users' => User::all(['id', 'name']),
            'employees' => Employee::all(['id', 'first_name', 'last_name']),
            'suppliers' => Supplier::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:bank_transfer,cash,check,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:pending,completed,failed,refunded',
            'paid_by' => 'nullable|exists:users,id',
            'payable_type' => 'nullable|string|in:App\\Models\\Employee,App\\Models\\Supplier,App\\Models\\User',
            'payable_id' => 'nullable|integer',
            'category' => 'required|string|max:255',
        ]);
        $validated['paid_by'] = $validated['paid_by'] ?? Auth::id();

        $payment->update($validated);

        return redirect()->route('payroll.index')->with('success', 'Paiement mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('payroll.index')->with('success', 'Paiement supprimé avec succès.');
    }
}
