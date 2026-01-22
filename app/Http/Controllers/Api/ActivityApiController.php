<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Expenses;
use App\Models\InstructionAnswer;
use App\Models\ServiceOrder;
use App\Models\SparePart;
use App\Models\SparePartActivity;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Notifications\Action;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActivityApiController extends Controller
{
    /**
     * Affiche la liste des activités de l'utilisateur authentifié.
     */
    public function index(Request $request)
    {

        // $user = User::where('id',13)->first();

        // if (!$user) {
        //     return response()->json(['message' => 'Non authentifié.'], 401);
        // }

        // $query = Activity::query()
        //     ->where(function ($query) use ($user) {
        //         $query->where(function ($q) use ($user) {
        //             $q->where('assignable_type', 'App\Models\User')
        //               ->where('assignable_id', $user->id);
        //         })->orWhere(function ($q) use ($user) {
        //             $q->where('assignable_type', 'App\Models\Team')
        //               ->whereIn('assignable_id', $user->teams->pluck('id'));
        //         });
        //     })
        //     ->with(['task', 'maintenance', 'sparePartActivities.sparePart', 'assignable', 'region', 'zone', 'equipment'])
        //     ->latest();

        // if ($request->has('search')) {
        //     $search = $request->input('search');
        //     $query->where(function ($q) use ($search) {
        //         $q->where('title', 'like', "%{$search}%")
        //           ->orWhere('problem_resolution_description', 'like', "%{$search}%")
        //           ->orWhereHas('task', fn ($subQ) => $subQ->where('title', 'like', "%{$search}%"));
        //     });
        // }

        // if ($request->has('status')) {
        //     $query->where('status', $request->input('status'));
        // }

        // $activities = $query->paginate($request->input('per_page', 15));
        $activities = Activity::all();
        return response()->json($activities);
    }

    /**
     * Affiche une activité spécifique.
     */
    public function show(Activity $activity)
    {
        // Politique de sécurité : s'assurer que le technicien ne peut voir que ses propres activités.
        $this->authorize('view', $activity);

        $activity->load([
            'task.instructions',
            'activityInstructions.answers',
            'instructionAnswers',
            'task.serviceOrders',
            'maintenance',
            'sparePartActivities.sparePart',
            'assignable',
            'region',
            'zone',
            'equipment'
        ]);

        return response()->json($activity);
    }

    /**
     * Met à jour une activité existante.
     * Idéal pour soumettre le rapport d'intervention depuis le mobile.
     */
    public function update(Request $request, Activity $activity)
    {
        $this->authorize('update', $activity);

        $validated = $request->validate([
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after_or_equal:actual_start_time',
            'status' => 'nullable|string',
            'problem_resolution_description' => 'nullable|string|max:65535',
            'proposals' => 'nullable|string|max:65535',
            'additional_information' => 'nullable|string|max:65535',
            'spare_parts_used' => 'nullable|array',
            'spare_parts_used.*.id' => 'required|exists:spare_parts,id',
            'spare_parts_used.*.quantity' => 'required|integer|min:1',
            'spare_parts_returned' => 'nullable|array',
            'spare_parts_returned.*.id' => 'required|exists:spare_parts,id',
            'spare_parts_returned.*.quantity' => 'required|integer|min:1',
            'instructions' => 'nullable|array',
            'instruction_answers' => 'nullable|array',
            'images_to_delete' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $activity->update(Arr::except($validated, [
                'instructions', 'instruction_answers', 'spare_parts_used', 'spare_parts_returned', 'images_to_delete'
            ]));

            $this->syncSpareParts($activity, $validated);
            $this->syncInstructionsAndAnswers($activity, $validated, $request->input('images_to_delete', []));
            $this->updateMaintenanceCost($activity);

            DB::commit();

            $activity->refresh()->load([
                'activityInstructions.answers',
                'sparePartActivities.sparePart',
            ]);

            return response()->json([
                'message' => 'Activité mise à jour avec succès.',
                'activity' => $activity,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur API Update Activity: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'activité.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronize spare parts for an activity.
     */
    private function syncSpareParts(Activity $activity, array $data)
    {
        $regionId = $activity->region_id;
        if (!$regionId) {
            throw new Exception("La région de l'activité n'est pas définie, impossible de gérer le stock.");
        }

        // Annuler les anciens mouvements et restaurer le stock
        foreach ($activity->sparePartActivities()->with('sparePart')->get() as $spa) {
            $partInRegion = SparePart::where('reference', $spa->sparePart->reference)
                                     ->where('region_id', $regionId)->first();
            if ($partInRegion) {
                if ($spa->type === 'used') {
                    $partInRegion->increment('quantity', $spa->quantity_used);
                } elseif ($spa->type === 'returned') {
                    $partInRegion->decrement('quantity', $spa->quantity_returned);
                }
            }
        }

        $activity->sparePartActivities()->delete();
        $activity->expenses()->where('category', 'parts')->delete();

        // Appliquer les nouveaux mouvements : UTILISÉS
        foreach ($data['spare_parts_used'] ?? [] as $partData) {
            $sparePart = SparePart::find($partData['id']);
            if ($sparePart) {
                $qty = $partData['quantity'];
                $partInRegion = SparePart::where('reference', $sparePart->reference)->where('region_id', $regionId)->first();

                if (!$partInRegion || $partInRegion->quantity < $qty) {
                    throw new Exception("Stock insuffisant pour la pièce {$sparePart->reference} dans la région. Requis: {$qty}, Disponible: " . ($partInRegion->quantity ?? 0));
                }

                $activity->sparePartActivities()->create([
                    'spare_part_id' => $sparePart->id, 'type' => 'used', 'quantity_used' => $qty,
                ]);

                if (!$activity->maintenance_id) {
                    $activity->expenses()->create([
                        'description' => 'Pièce utilisée: ' . $sparePart->reference,
                        'amount' => ($sparePart->price ?? 0) * $qty,
                        'category' => 'parts', 'user_id' => Auth::id(), 'expense_date' => now(), 'status' => 'pending'
                    ]);
                }
                $partInRegion->decrement('quantity', $qty);
            }
        }

        // Appliquer les nouveaux mouvements : RETOURNÉS
        foreach ($data['spare_parts_returned'] ?? [] as $partData) {
            $sparePart = SparePart::find($partData['id']);
            if ($sparePart) {
                $qty = $partData['quantity'];
                $partInRegion = SparePart::firstOrCreate(
                    ['reference' => $sparePart->reference, 'region_id' => $regionId],
                    [
                        'label_id' => $sparePart->label_id, 'price' => $sparePart->price,
                        'min_quantity' => $sparePart->min_quantity, 'quantity' => 0, 'user_id' => Auth::id()
                    ]
                );
                $partInRegion->increment('quantity', $qty);

                $activity->sparePartActivities()->create([
                    'spare_part_id' => $sparePart->id, 'type' => 'returned', 'quantity_returned' => $qty,
                ]);
            }
        }
    }

    /**
     * Synchronize instructions and their answers.
     */
    private function syncInstructionsAndAnswers(Activity $activity, array $data, array $imagesToDelete = [])
    {
        // Les instructions sont généralement définies par la tâche/maintenance et ne sont pas modifiées par le technicien.
        // Nous nous concentrons sur la mise à jour des réponses.

        $instructionIdMap = $activity->activityInstructions()->pluck('id', 'id')->toArray();

        // Supprimer les fichiers marqués pour suppression
        $this->deleteStoredFiles($imagesToDelete);

        foreach ($data['instruction_answers'] ?? [] as $instructionId => $value) {
            // S'assurer que la réponse correspond à une instruction valide pour cette activité
            if (!isset($instructionIdMap[$instructionId])) {
                continue;
            }

            $instruction = $activity->activityInstructions()->find($instructionId);
            $finalValue = $value;

            if (in_array($instruction->type, ['image', 'signature'])) {
                if (is_array($value)) {
                    $existingAnswer = InstructionAnswer::where('activity_id', $activity->id)
                        ->where('activity_instruction_id', $instructionId)->first();

                    $existingPaths = $existingAnswer && !empty($existingAnswer->value) ? json_decode($existingAnswer->value, true) : [];
                    if (!is_array($existingPaths)) $existingPaths = [];

                    $keptPaths = array_diff($existingPaths, $imagesToDelete);

                    $newPaths = [];
                    foreach ($value as $file) {
                        if (is_a($file, 'Illuminate\Http\UploadedFile')) {
                            $newPaths[] = $file->store('instruction_answers', 'public');
                        }
                    }
                    $finalValue = json_encode(array_merge($keptPaths, $newPaths));
                }
            }

            InstructionAnswer::updateOrCreate(
                ['activity_id' => $activity->id, 'activity_instruction_id' => $instructionId],
                ['value' => $finalValue, 'user_id' => Auth::id()]
            );
        }
    }

    /**
     * Delete files from storage.
     */
    private function deleteStoredFiles(array $filesToDelete)
    {
        foreach ($filesToDelete as $filePath) {
            if (str_starts_with($filePath, 'instruction_answers/')) {
                Storage::disk('public')->delete($filePath);
            }
        }
    }

    /**
     * Recalculate and update the cost of the parent maintenance.
     */
    private function updateMaintenanceCost(Activity $activity)
    {
        $maintenance = $activity->maintenance ?? $activity->parent->maintenance ?? null;
        if (!$maintenance) return;

        // Cette logique est complexe et peut être centralisée dans un Service ou un Observer.
        // Pour l'instant, on la garde ici pour la cohérence avec ActivityController.

        $maintenance->expenses()->whereIn('category', ['parts', 'labor_technician', 'labor_tacheron'])->delete();

        $allActivities = Activity::where('maintenance_id', $maintenance->id)
            ->orWhereIn('parent_id', function ($query) use ($maintenance) {
                $query->select('id')->from('activities')->where('maintenance_id', $maintenance->id);
            })
            ->with(['assignable', 'sparePartActivities.sparePart'])
            ->get();

        $totalMaterialCost = 0;
        $totalTechnicianLaborCost = 0;
        $totalTacheronLaborCost = 0;

        $tacheronRate = 0.76;
        $technicianRate = 2.92;

        foreach ($allActivities as $act) {
            foreach ($act->sparePartActivities->where('type', 'used') as $spa) {
                $totalMaterialCost += ($spa->sparePart->price ?? 0) * $spa->quantity_used;
            }

            if ($act->actual_start_time && $act->actual_end_time) {
                $durationInHours = $act->actual_start_time->diffInHours($act->actual_end_time);
                if ($durationInHours > 0) {
                    $assignable = $act->assignable;
                    if ($assignable instanceof \App\Models\Team) {
                        $totalTechnicianLaborCost += ($assignable->members()->count() * $technicianRate * $durationInHours);
                        $totalTacheronLaborCost += (($assignable->nombre_tacherons ?? 0) * $tacheronRate * $durationInHours);
                    } elseif ($assignable instanceof \App\Models\User) {
                        $totalTechnicianLaborCost += (1 * $technicianRate * $durationInHours);
                    }
                }
            }
        }

        if ($totalMaterialCost > 0) {
            $maintenance->expenses()->create([
                'description' => 'Coût total des pièces pour la maintenance #' . $maintenance->id,
                'amount' => $totalMaterialCost, 'category' => 'parts', 'user_id' => Auth::id(),
                'expense_date' => now(), 'status' => 'pending',
            ]);
        }

        if ($totalTechnicianLaborCost > 0) {
            $maintenance->expenses()->create([
                'description' => 'Coût total main d\'œuvre (Techniciens) pour la maintenance #' . $maintenance->id,
                'amount' => $totalTechnicianLaborCost, 'category' => 'labor_technician', 'user_id' => Auth::id(),
                'expense_date' => now(), 'status' => 'pending',
            ]);
        }

        if ($totalTacheronLaborCost > 0) {
            $maintenance->expenses()->create([
                'description' => 'Coût total main d\'œuvre (Tâcherons) pour la maintenance #' . $maintenance->id,
                'amount' => $totalTacheronLaborCost, 'category' => 'labor_tacheron', 'user_id' => Auth::id(),
                'expense_date' => now(), 'status' => 'pending',
            ]);
        }

        $maintenance->labor_cost = $totalTechnicianLaborCost + $totalTacheronLaborCost;
        $maintenance->material_cost = $totalMaterialCost;
        $maintenance->cost = $maintenance->labor_cost + $maintenance->material_cost;
        $maintenance->save();
    }
}
