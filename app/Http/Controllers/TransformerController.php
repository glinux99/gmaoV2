<?php

namespace App\Http\Controllers;

use App\Models\{Equipment, NetworkNode, Transformer};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TransformerController extends Controller
{
    /**
     * Liste et Recherche avancée
     */
    public function index(Request $request)
    {

        $query = Transformer::with(['equipment:id,designation', 'networkNode:id,name']);

        // 1. Recherche globale (Texte) sur plusieurs champs et relations
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($subQ) use ($search) {
                $subQ->where('transformer_id', 'like', "%{$search}%")
                     ->orWhereHas('equipment', function ($eqQ) use ($search) {
                         $eqQ->where('designation', 'like', "%{$search}%");
                     })
                     ->orWhereHas('networkNode', function ($nodeQ) use ($search) {
                         $nodeQ->where('name', 'like', "%{$search}%");
                     });
            });
        });

        // 2. Filtres spécifiques (Dropdowns)
        $query->when($request->status, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($request->equipment_id, function ($q, $equipment_id) {
            $q->where('equipment_id', $equipment_id);
        });

        $query->when($request->network_node_id, function ($q, $network_node_id) {
            $q->where('network_node_id', $network_node_id);
        });

        // 3. Filtre par date (Période)
        $query->when($request->date_from, function ($q, $date) {
            $q->whereDate('measured_at', '>=', $date);
        });

        $query->when($request->date_to, function ($q, $date) {
            $q->whereDate('measured_at', '<=', $date);
        });
        $transformers = $query->latest('measured_at')->paginate(15);

        // Réponse API
        if ($request->wantsJson()) {
            return response()->json($transformers);
        }

        // Réponse Inertia
        return Inertia::render('Transformers', [
            'transformers' => $transformers->withQueryString(), // withQueryString garde les paramètres de pagination/recherche
            'equipments' => Equipment::select('id', 'designation')->get(),
            'networkNodes' => NetworkNode::select('id')->get(),
            'filters' => $request->only(['search', 'status', 'equipment_id', 'network_node_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Création
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        $validated['uuid'] = (string) Str::uuid();
        $transformer = Transformer::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transformateur créé avec succès',
                'data' => $transformer
            ], 201);
        }

        return redirect()->back()->with('success', 'Enregistré avec succès.');
    }

    /**
     * Affichage d'un élément (CORRIGÉ : Ajout de Request $request)
     */
  public function show(Request $request, Transformer $transformer)
{
    $transformer->load(['equipment', 'networkNode']);
    $history = $transformer->orderBy('measured_at', 'desc')
                           ->take(100)
                           ->get();
    $equipments = Equipment::select('id', 'designation')->get();
    $networkNodes = NetworkNode::get();
    $filters = $request->only(['date_from', 'date_to', 'search']);
    $query = Transformer::with(['equipment:id,designation', 'networkNode:id,name']);
    $transformers = $query->latest('measured_at')->paginate(15);
    $payload = [
        'transformer'  => $transformer,
        'transformers'  => $transformers,
        'history'      => $history,
        'equipments'   => $equipments,
        'networkNodes' => $networkNodes,
       'filters' => $request->only(['search', 'status', 'equipment_id', 'network_node_id', 'date_from', 'date_to']),
    ];

    // 6. Réponse API ou Inertia
    if ($request->wantsJson()) {
        return response()->json($payload);
    }

    return Inertia::render('TransformerDetail', $payload);
}

    /**
     * Mise à jour (CORRIGÉ : Sécurisé avec validation au lieu de $request->all())
     */
    public function update(Request $request, Transformer $transformer)
    {
        // On passe 'true' pour rendre les champs optionnels (pour les mises à jour partielles/PATCH)
        $validated = $request->validate($this->validationRules(true));

        $transformer->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transformateur mis à jour',
                'data' => $transformer
            ]);
        }

        return redirect()->back()->with('success', 'Mis à jour avec succès.');
    }

    /**
     * Suppression d'un élément
     */
    public function destroy(Request $request, Transformer $transformer)
    {
        $transformer->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Transformateur supprimé'], 204);
        }

        return redirect()->route('transformers.index')->with('success', 'Supprimé avec succès.');
    }

    /**
     * NOUVEAU : Suppression en masse (Bulk Delete)
     * Très utile pour les tableaux de bord (cases à cocher multiples)
     */
    public function destroyMany(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:transformers,id'
        ]);

        Transformer::whereIn('id', $request->ids)->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Éléments supprimés'], 204);
        }

        return redirect()->back()->with('success', 'Les éléments sélectionnés ont été supprimés.');
    }

    /**
     * NOUVEAU : Centralisation des règles de validation
     * @param bool $isUpdate Permet de rendre les champs optionnels lors d'un update
     */
    private function validationRules(bool $isUpdate = false): array
    {
        $req = $isUpdate ? 'sometimes|' : 'required|';

        return [
            'transformer_id'  => $req . 'string|max:255',
            'uuid'            => 'nullable|uuid',
            'measured_at'     => $req . 'date',
            'temperature_alarm' => 'nullable|boolean',
            'pressure_alarm'    => 'nullable|boolean',
            'oil_level_alarm'   => 'nullable|boolean',
            'dmcr_alarm'        => 'nullable|boolean',
            'dmcr_trip'         => 'nullable|boolean',
            'status'          => $req . 'in:operational,maintenance,alert,offline',
            'load_percentage' => 'nullable|numeric|between:0,200',
            'oil_temperature' => 'nullable|numeric',
            'ambient_temperature' => 'nullable|numeric',
            'equipment_id'    => 'nullable|exists:equipment,id',
            'network_node_id' => 'nullable|exists:network_nodes,id',
            'metadata'        => 'nullable|array',
        ];
    }
}
