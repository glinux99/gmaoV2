<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = request();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Leave::with('user', 'approvedBy')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (request()->has('search')) {
            $search = request('search');
            $query->where('type', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        return Inertia::render('HumanResources/Leaves', [
            'leaves' => $query->paginate(10),
            'filters' => request()->only(['search', 'start_date', 'end_date']),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('document_file')) {

            $validated['document_path'] = $request->file('document_file')->store('leave_documents', 'public');
        }

        Leave::create($validated);

        return redirect()->route('leaves.index')->with('success', 'Demande de congé créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Leave $leave)
 {
 return Inertia::render('HumanResources/Leaves/Show', [
 'leave' => $leave->load('user'),
 ]);
 }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Leave $leave)
    {
        return Inertia::render('HumanResources/Leaves', [
            'leave' => $leave->load('user'),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('document_file')) {
            if ($leave->document_path) {
                Storage::disk('public')->delete($leave->document_path);
            }
            $validated['document_path'] = $request->file('document_file')->store('leave_documents', 'public');
        }

        $leave->update($validated);

        return redirect()->route('leaves.index')->with('success', 'Demande de congé mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leave $leave)
    {
        $leave->delete();

        return redirect()->route('leaves.index')->with('success', 'Demande de congé supprimée avec succès.');
    }

    /**
     * Update the status of the specified leave request.
     */
    public function updateStatus(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected',
        ]);

        $updateData = ['status' => $validated['status']];

        if (in_array($validated['status'], ['approved', 'rejected'])) {
            $updateData['approved_by'] = Auth::id();
            $updateData['approval_date'] = now();
        }

        $leave->update($updateData);

        return redirect()->route('leaves.index')->with('success', 'Statut de la demande de congé mis à jour avec succès.');
    }
}
