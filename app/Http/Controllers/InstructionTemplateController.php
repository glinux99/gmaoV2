<?php

namespace App\Http\Controllers;

use App\Models\InstructionTemplate;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InstructionTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InstructionTemplate::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Configurations/InstructionTemplates', [
            'instructionTemplates' => $query->paginate(10),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */


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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:instruction_templates,name',
            'instructions' => 'required|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string|in:text,number,date,boolean,select,file',
            'instructions.*.is_required' => 'boolean',
        ]);

        InstructionTemplate::create($validated);
        return redirect()->back()->with('success', 'Modèle d\'instruction créé avec succès.');
    }


    /**
     * Display the specified resource.
     */
    public function show(InstructionTemplate $instructionTemplate)
    {
        return Inertia::render('Configurations/InstructionTemplates/Show', [
            'instructionTemplate' => $instructionTemplate,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InstructionTemplate $instructionTemplate)
    {
        return Inertia::render('Configurations/InstructionTemplates', [
            'instructionTemplate' => $instructionTemplate,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InstructionTemplate $instructionTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:instruction_templates,name,' . $instructionTemplate->id,
            'instructions' => 'required|array',
            'instructions.*.label' => 'required|string|max:255',
            'instructions.*.type' => 'required|string|in:text,number,date,boolean,select,file',
            'instructions.*.is_required' => 'boolean',
        ]);

        $instructionTemplate->update($validated);
        return redirect()->route('instruction-templates.index')->with('success', 'Modèle d\'instruction mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InstructionTemplate $instructionTemplate)
    {
        // Optionally, check for dependencies before deleting
        // if ($instructionTemplate->maintenances()->exists()) {
        //     return redirect()->back()->with('error', 'Ce modèle est utilisé et ne peut pas être supprimé.');
        // }
        $instructionTemplate->delete();
        return redirect()->route('instruction-templates.index')->with('success', 'Modèle d\'instruction supprimé avec succès.');
    }
}
