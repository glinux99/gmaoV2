<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = request();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Employee::with('user')
            ->whereBetween('created_at', [$startDate, $endDate]);


        if (request()->has('search')) {
            $search = request('search');
            $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('job_title', 'like', '%' . $search . '%')
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        return Inertia::render('HumanResources/Employees', [
            'employees' => $query->paginate(10)->withQueryString(),
            'filters' => request()->only(['search']),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not typically used for Inertia CRUD, as forms are often part of the index page
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id',
            'employee_id' => 'nullable|string|max:255|unique:employees,employee_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees,email',
            'phone_number' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'hire_date' => 'required|date',
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric',
            'employment_status' => 'required|in:active,on_leave,terminated',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'notes' => 'nullable|string',
            'cv_path' => 'nullable|string',
            'id_card_path' => 'nullable|string',
            'contract_path' => 'nullable|string',
            'other_documents' => 'nullable|json',
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        // Not typically used for Inertia CRUD, but can be implemented if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        // Not typically used for Inertia CRUD, as forms are often part of the index page
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id,' . $employee->id,
            'employee_id' => 'nullable|string|max:255|unique:employees,employee_id,' . $employee->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees,email,' . $employee->id,
            'phone_number' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'hire_date' => 'required|date',
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric',
            'employment_status' => 'required|in:active,on_leave,terminated',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'notes' => 'nullable|string',
            'cv_path' => 'nullable|string',
            'id_card_path' => 'nullable|string',
            'contract_path' => 'nullable|string',
            'other_documents' => 'nullable|json',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
