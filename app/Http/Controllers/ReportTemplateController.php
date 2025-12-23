<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use Illuminate\Http\Request;

class ReportTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Récupère tous les modèles et les retourne en JSON.
        return response()->json(ReportTemplate::latest()->get(['id', 'name']));
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
        // Valide les données reçues du frontend.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|json', // S'assure que le contenu est bien du JSON.
        ]);

        // Crée le nouveau modèle de rapport.
        $reportTemplate = ReportTemplate::create($validated);

        // Retourne le modèle nouvellement créé pour que le frontend puisse l'ajouter à sa liste.
        return response()->json($reportTemplate, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportTemplate $reportTemplate)
    {
        // Retourne le modèle spécifique demandé par le frontend.
        return response()->json($reportTemplate);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReportTemplate $reportTemplate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReportTemplate $reportTemplate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportTemplate $reportTemplate)
    {
        // Supprime le modèle de rapport de la base de données.
        $reportTemplate->delete();

        // Retourne une réponse vide avec un statut 204 (No Content) pour indiquer le succès.
        return response()->noContent();
    }
}
