<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Label;
use App\Models\EquipmentMovement;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class EquipmentController extends Controller
{
    /**
     * Affiche la liste des équipements avec pagination et filtres.
     */
    public function index()
    {
        $request = request();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Equipment::with(['equipmentType', 'region', 'user', 'parent'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('purchase_date', [$startDate, $endDate]);
            });

        // --- Logique de recherche (Filtre global) ---
        if (request()->has('search') && $search = request('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('tag', 'like', '%' . $search . '%')
                  ->orWhere('designation', 'like', '%' . $search . '%')
                  ->orWhere('brand', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%')
                  ->orWhere('serial_number', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%')
                  // Recherche dans la relation EquipmentType
                  ->orWhereHas('equipmentType', fn ($sq) => $sq->where('name', 'like', '%' . $search . '%'))
                  // Recherche dans la relation Region
                  ->orWhereHas('region', fn ($sq) => $sq->where('designation', 'like', '%' . $search . '%'));
            });
        }

        // --- Chargement des équipements parents disponibles pour la création d'enfants ---
        $parentEquipments = Equipment::whereNull('parent_id') // Équipements qui ne sont pas des enfants
                                    ->where('status', 'en stock') // Seulement ceux qui sont en stock
                                    ->where('quantity', '>', 0) // Quantité disponible
                                    ->get(['id', 'tag', 'designation', 'brand', 'model', 'equipment_type_id', 'region_id', 'quantity', 'status', 'characteristics']);

        return Inertia::render('Actifs/Equipments', [
            // Correction: Ajout de withQueryString() pour conserver le filtre de recherche lors de la pagination
            'equipments' => $query->paginate(10),
            'filters' => request()->only(['search']),
            'equipmentTypes' => EquipmentType::all(),
            'regions' => Region::get(),
            'users' => User::all(['id', 'name']),
            'labels' => Label::with('labelCharacteristics')->get(),
            'parentEquipments' => $parentEquipments,
        ]);
    }

    /**
     * Crée un nouvel équipement (parent ou enfant).
     */
    public function store(Request $request)
    {
        // Validation (Correction des noms de table pour 'unique')
        if($request['parent_id']!=null){
            $request['child_quantity']= $request['quantity'];
        }
        $validated = $request->validate([
            'tag' => 'nullable|string|max:255',
            'designation' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:equipment,serial_number',
            'status' => 'required|in:en service,en panne,en maintenance,hors service,en stock',
            'location' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0|required_if:status,en stock',
            'purchase_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'equipment_type_id' => 'nullable|exists:equipment_types,id',
            'region_id' => 'nullable|exists:regions,id',
            'parent_id' => 'nullable|exists:equipment,id',
            // Validation spécifique pour la création d'enfants
            'child_quantity' => 'nullable|integer|min:1|required_with:parent_id',
            'characteristics' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Retirer les champs non persistants de la collection de données principale
            $equipmentData = collect($validated)->except(['characteristics', 'child_quantity', 'label_id'])->all();
            $equipmentData['user_id'] = Auth::id();

            // Si le statut n'est pas "en stock", la quantité doit être 1 (quantité suivie uniquement pour le stock)
            if (($equipmentData['status'] ?? null) !== 'en stock') {
                $equipmentData['quantity'] = 1;
            }

            $requestedQuantity = $validated['child_quantity'] ?? 1;

            // Gestion de la création d'équipements enfants (à partir d'un parent en stock)
            if (!empty($validated['parent_id'])) {
                $parent = Equipment::find($validated['parent_id']);

                if (!$parent) {
                    throw ValidationException::withMessages(['parent_id' => 'Le parent spécifié est introuvable.']);
                }

                // Si equipment_type_id n'est pas fournie, prendre celle du parent
                if (empty($equipmentData['equipment_type_id'])) {
                    $equipmentData['equipment_type_id'] = $parent->equipment_type_id;
                }

                if ($parent->status !== 'en stock' || $parent->quantity < $requestedQuantity) {
                    throw ValidationException::withMessages([
                        'child_quantity' => 'La quantité du parent est insuffisante ou le parent n\'est pas en stock.',
                    ]);
                }

                // Création des enfants
                for ($i = 0; $i < $requestedQuantity; $i++) {
                    $equipmentChildData = $equipmentData;

                    // Les enfants ne doivent pas hériter du tag ou du serial_number du parent
                    $equipmentChildData['tag'] = null;
                    $equipmentChildData['serial_number'] = null;
                    $equipmentChildData['location'] = null; // Un enfant a sa propre location s'il quitte le stock

                    // Générer un tag unique pour l'enfant
                    $baseTag = $parent->tag ?: $parent->designation;
                    $suffix = Equipment::where('parent_id', $parent->id)->count() + $i + 1;
                    $equipmentChildData['tag'] = $this->generateUniqueTag($baseTag, $suffix);

                    // Les enfants sont 'en service' par défaut lors de la sortie de stock
                    $equipmentChildData['status'] = 'en service';
                    $equipmentChildData['quantity'] = 1; // Chaque enfant créé est une unité

                    // Traiter les caractéristiques (héritage des données soumises)
                    $equipmentChildData['characteristics'] = $this->processCharacteristics($validated['characteristics'] ?? [], $request);

                    $childEquipment = Equipment::create($equipmentChildData);

                    // Log creation movement
                    EquipmentMovement::create([
                        'equipment_id' => $childEquipment->id,
                        'user_id' => Auth::id(),
                        'type' => 'création',
                        'description' => 'Équipement enfant créé à partir du parent ' . $parent->tag . ' avec le statut : ' . $childEquipment->status,
                    ]);
                }

                // Décrémenter la quantité du parent
                $parent->decrement('quantity', $requestedQuantity);

            } else {
                // Création d'un équipement parent (ou un équipement simple)
                $equipmentData['characteristics'] = $this->processCharacteristics($validated['characteristics'] ?? [], $request);
                $equipment = Equipment::create($equipmentData);

                // Log creation movement
                EquipmentMovement::create([
                    'equipment_id' => $equipment->id,
                    'user_id' => Auth::id(),
                    'type' => 'création',
                    'description' => 'Équipement créé avec le statut : ' . $equipment->status,
                ]);
            }
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement(s) créé(s) avec succès.');
    }

    /**
     * Met à jour un équipement existant.
     */
    public function update(Request $request, Equipment $equipment)
    {
        // Correction: Ajout de l'ID pour la règle unique
          if($request['parent_id']!=null){
            $request['child_quantity']= $request['quantity'];
        }
        $validated = $request->validate([
            'tag' => 'nullable|string|max:255,' . $equipment->id,
            'designation' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:equipment,serial_number,' . $equipment->id,
            'status' => 'required|in:en service,en panne,en maintenance,hors service,en stock',
            'location' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0|required_if:status,en stock',
            'purchase_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'equipment_type_id' => 'nullable|exists:equipment_types,id',
            'region_id' => 'nullable|exists:regions,id',
            'parent_id' => 'nullable|exists:equipment,id',
            'label_id' => 'nullable|exists:labels,id',
            'characteristics' => 'nullable|array',
        ]);

        DB::transaction(function () use ($equipment, $validated, $request) {
            $originalStatus = $equipment->status;
            $originalParentId = $equipment->parent_id;
            $originalQuantity = $equipment->quantity;

            $newParentId = $validated['parent_id'] ?? null;
            $newQuantity = $validated['quantity'] ?? $originalQuantity;

            // Si equipment_type_id n'est pas fournie et qu'un parent est défini, prendre celle du parent
            if (empty($validated['equipment_type_id']) && $newParentId) {
                $validated['equipment_type_id'] = Equipment::find($newParentId)->equipment_type_id ?? null;
            }

            // --- Gestion du changement de parent ---
            if ($newParentId !== $originalParentId) {
                // 1. Rétablir la quantité à l'ancien parent si l'équipement était un enfant
                if ($originalParentId) {
                    $oldParent = Equipment::find($originalParentId);
                    if ($oldParent && $originalParentId !== $equipment->id) {
                        $oldParent->increment('quantity', $originalQuantity);
                    }
                }

                // 2. Décrémenter la quantité du nouveau parent si l'équipement devient un enfant
                if ($newParentId) {
                    $newParent = Equipment::find($newParentId);

                    if ($newParent && $newParentId !== $equipment->id) {
                        if ($newParent->status !== 'en stock' || $newParent->quantity < $newQuantity) {
                            throw ValidationException::withMessages(['parent_id' => 'La quantité du nouveau parent est insuffisante ou il n\'est pas en stock.']);
                        }
                        $newParent->decrement('quantity', $newQuantity);
                    }
                }
            }

            // Mise à jour des données principales (sans les caractéristiques)
            $equipment->fill(collect($validated)->except(['characteristics'])->all());

            // Si le statut n'est pas "en stock", la quantité doit rester à 1
            if ($equipment->status !== 'en stock') {
                $equipment->quantity = 1;
            }

            // Gérer les logs de changement de statut
            if ($equipment->isDirty('status')) {
                EquipmentMovement::create([
                    'equipment_id' => $equipment->id,
                    'user_id' => Auth::id(),
                    'type' => 'changement_statut',
                    'description' => "Statut changé de '{$originalStatus}' à '{$validated['status']}'.",
                ]);
            }

            // Traiter et mettre à jour les caractéristiques
            $equipment->characteristics = $this->processCharacteristics($validated['characteristics'] ?? [], $request, $equipment->characteristics);
            $equipment->save();
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement mis à jour avec succès.');
    }

    /**
     * Supprime un équipement.
     */
    public function destroy(Equipment $equipment)
    {
        DB::transaction(function () use ($equipment) {
            // Loguer la suppression
            EquipmentMovement::create([
                'equipment_id' => $equipment->id,
                'user_id' => Auth::id(),
                'type' => 'suppression',
                'description' => 'Équipement ' . $equipment->tag . ' supprimé.',
            ]);

            // Correction: Si l'équipement supprimé est un enfant, restaurer la quantité au parent
            if ($equipment->parent_id) {
                $parent = $equipment->parent;
                if ($parent) {
                    // On incrémente le parent avec la quantité de l'équipement supprimé
                    $parent->increment('quantity', $equipment->quantity ?? 1);
                }
            }

            // Supprimer l'équipement
            $equipment->delete();
        });

        return redirect()->route('equipments.index')->with('success', 'Équipement supprimé avec succès.');
    }

    /**
     * Supprime plusieurs équipements.
     */
    public function bulkDestroy(Request $request)
    {
        // Payload attendu: { "ids": [33,34,35,...] }
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:equipment,id',
        ]);

        DB::transaction(function () use ($validated) {

            // Récupérer les équipements ciblés avec les colonnes utiles
            $equipments = Equipment::whereIn('id', $validated['ids'])
                ->get(['id', 'tag', 'parent_id', 'quantity']);

            if ($equipments->isEmpty()) {
                return;
            }

            // 1) Réajuster la quantité des parents pour les enfants supprimés
            $incrementsByParent = $equipments
                ->whereNotNull('parent_id')
                ->groupBy('parent_id')
                ->map(function ($group) {
                    // Si quantity est null, considérer 1 par défaut
                    return (int) $group->sum(fn($eq) => $eq->quantity ?? 1);
                });

            foreach ($incrementsByParent as $parentId => $qtyToAdd) {
                if ($qtyToAdd > 0) {
                    Equipment::where('id', $parentId)->increment('quantity', $qtyToAdd);
                }
            }

            // 2) Journaliser chaque suppression
            foreach ($equipments as $equipment) {
                EquipmentMovement::create([
                    'equipment_id' => $equipment->id,
                    'user_id' => Auth::id(),
                    'type' => 'suppression',
                    'description' => 'Équipement ' . ($equipment->tag ?? $equipment->id) . ' supprimé en masse.',
                ]);
            }

            // 3) Suppression en masse
            Equipment::whereIn('id', $validated['ids'])->delete();
        });

        return redirect()->route('equipments.index')->with('success', 'Équipements sélectionnés supprimés avec succès.');
    }

    // --- Fonctions utilitaires ---

    /**
     * Fonction utilitaire pour synchroniser les caractéristiques et gérer les fichiers.
     * @param array $characteristics
     * @param Request $request
     * @param array|null $existingCharacteristics
     * @return array
     */
    private function processCharacteristics(array $characteristics, Request $request, ?array $existingCharacteristics = []): array
    {
        $processedCharacteristics = [];

        // Convertir les caractéristiques existantes en tableau associatif par 'name' pour un accès facile
        $existingByName = collect($existingCharacteristics)->keyBy('name')->all();

        foreach ($characteristics as $index => $charData) {
            // Ignorer les caractéristiques vides (si l'utilisateur a laissé la ligne vide dans le formulaire)
            if (empty($charData['name'])) {
                continue;
            }

            $value = $charData['value'] ?? null;
            $charName = $charData['name'];

            if ($charData['type'] === 'file') {
                // Si un nouveau fichier est uploadé pour cette caractéristique
                if ($request->hasFile("characteristics.{$index}.value")) {
                    $file = $request->file("characteristics.{$index}.value");
                    // Supprimer l'ancien fichier s'il existe
                    if (isset($existingByName[$charName]['value']) && $existingByName[$charName]['value']) {
                        Storage::disk('public')->delete($existingByName[$charName]['value']);
                    }
                    $value = $file->store('equipments_files', 'public');
                } else {
                    // Conserver l'ancienne valeur si aucun nouveau fichier n'est envoyé
                    $value = $existingByName[$charName]['value'] ?? null;
                }
            } else if ($charData['type'] === 'boolean') {
                // Convertir la valeur booléenne (peut être reçue comme 'true' ou 'false' string, ou null)
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            $processedCharacteristics[] = [
                'name' => $charData['name'],
                'type' => $charData['type'],
                'value' => $value,
            ];
        }
        return $processedCharacteristics;
    }

    /**
     * Fonction utilitaire pour générer un tag unique.
     * @param string $baseTag
     * @param int $suffix
     * @return string
     */
    private function generateUniqueTag(string $baseTag, int $suffix): string
    {
        $i = $suffix;
        // Nettoyer le tag de base et supprimer les caractères spéciaux ou espaces
        $cleanBaseTag = trim(strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $baseTag)));

        do {
            $tag = $cleanBaseTag . '-' . str_pad($i, 3, '0', STR_PAD_LEFT); // Ex: PC-001, PC-002
            $i++;
        } while (Equipment::where('tag', $tag)->exists());

        return $tag;
    }
}
