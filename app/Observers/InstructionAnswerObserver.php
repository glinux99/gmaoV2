<?php

namespace App\Observers;

use App\Models\InstructionAnswer;
use App\Models\MaintenanceInstruction;
use App\Models\ActivityInstruction;
use Illuminate\Support\Facades\Log;

class InstructionAnswerObserver
{
    // Flag statique pour prévenir les boucles de synchronisation infinies.
    private static bool $isSyncing = false;

    /**
     * Gère l'événement "created" (création) d'une InstructionAnswer.
     *
     * @param  \App\Models\InstructionAnswer  $instructionAnswer
     * @return void
     */
    public function saved(InstructionAnswer $instructionAnswer)
    {
        // Si une synchronisation est déjà en cours, on sort pour éviter une boucle.
        if (self::$isSyncing) {
            return;
        }

        try {
            self::$isSyncing = true; // On active le flag

            // Cas 1: La réponse est liée à une instruction de Maintenance
            if ($instructionAnswer->maintenance_instruction_id) {
                $this->syncFromMaintenanceToActivity($instructionAnswer);
            } elseif ($instructionAnswer->activity_instruction_id) { // Cas 2: La réponse est liée à une instruction d'Activité
                $this->syncFromActivityToMaintenance($instructionAnswer);
            }
        } finally {
            self::$isSyncing = false; // On désactive le flag dans tous les cas
        }
    }

    /**
     * Synchronise une réponse depuis une instruction de Maintenance vers l'instruction d'Activité correspondante.
     */
    private function syncFromMaintenanceToActivity(InstructionAnswer $sourceAnswer)
    {
        // OPTIMISATION: Eager load des relations pour éviter les requêtes N+1.
        $maintenanceInstruction = MaintenanceInstruction::with('maintenance.activity.activityInstructions')
            ->find($sourceAnswer->maintenance_instruction_id);

        if (!$maintenanceInstruction || !$maintenanceInstruction->maintenance || !$maintenanceInstruction->maintenance->activity) {
            return;
        }

        $activity = $maintenanceInstruction->maintenance->activity;

        // OPTIMISATION: Recherche dans la collection déjà chargée en mémoire.
        $targetInstruction = $activity->activityInstructions()
            ->where('label', $maintenanceInstruction->label)
            ->first();


        if ($targetInstruction) {
            // Mettre à jour ou créer la réponse pour l'instruction d'activité
            InstructionAnswer::updateOrCreate(
                [
                    'activity_id' => $activity->id,
                    'activity_instruction_id' => $targetInstruction->id,
                ],
                [
                    'value' => $sourceAnswer->value,
                    'user_id' => $sourceAnswer->user_id,
                ]
            );
        }
    }

    /**
     * Synchronise une réponse depuis une instruction d'Activité vers l'instruction de Maintenance correspondante.
     */
    private function syncFromActivityToMaintenance(InstructionAnswer $sourceAnswer)
    {
        $activityInstruction = ActivityInstruction::find($sourceAnswer->activity_instruction_id);
        if (!$activityInstruction || !$activityInstruction->activity || !$activityInstruction->activity->maintenance) {
            return;
        }

        $maintenance = $activityInstruction->activity->maintenance;

        // Trouver l'instruction de maintenance qui correspond
        $targetInstruction = $maintenance->instructions()
            ->where('label', $activityInstruction->label)
            ->first();

        if ($targetInstruction) {
            // Mettre à jour ou créer la réponse pour l'instruction de maintenance
            InstructionAnswer::updateOrCreate(
                [
                    'activity_id' => null, // Important: Ne pas lier à une activité
                    'maintenance_instruction_id' => $targetInstruction->id,
                ],
                [
                    'value' => $sourceAnswer->value,
                    'user_id' => $sourceAnswer->user_id,
                ]
            );
        }
    }

    /**
     * Gère l'événement "deleted" (suppression) d'une InstructionAnswer.
     *
     * @param  \App\Models\InstructionAnswer  $instructionAnswer
     * @return void
     */
    public function deleted(InstructionAnswer $instructionAnswer)
    {
        // Logique de suppression synchronisée si nécessaire
    }
}
