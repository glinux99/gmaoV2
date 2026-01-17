<?php

namespace App\Imports;

use App\Models\Activity;
use App\Models\Connection;
use App\Models\InterventionRequest;
use App\Models\Region;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InterventionRequestImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        // 1. Validation de base
        $complaintId = $row['id_plainte'] ?? null;
        if (empty($complaintId)) {
            return null;
        }

        // 2. Recherche des entités liées (Optimisation : recherche par code)
        $connection = !empty($row['code_client'])
            ? Connection::where('customer_code', $row['code_client'])->first()
            : null;

        $region = !empty($row['region'])
            ? Region::where('designation', 'like', '%' . $row['region'] . '%')->first()
            : null;

        $zone = $this->findZone($row['zone'] ?? null);

        $assignee = $this->findAssignee($row);

        // 3. Transformation des dates et états
        $scheduledDate = $this->transformDate($row['date_dintervention'] ?? null);
        $status = strtolower($row['etat'] ?? 'pending');
        $isClosed = in_array($status, ['clôturé', 'closed', 'termine']);

        // 4. Création ou Mise à jour de l'Intervention
        $interventionRequest = InterventionRequest::updateOrCreate(
            ['title' => $complaintId], // On utilise l'ID plainte comme clé unique
            [
                'description' => $row['commentaire'] ?? $row['plainte_soumise'] ?? null,
                'status' => $status,
                'priority' => $row['priorite'] ?? 'Moyenne',
                'intervention_reason' => $row['raison_dintervention'] ?? null,
                'category' => $row['nature_dintervention'] ?? null,
                'min_time_hours' => is_numeric($row['temps_dintervention']) ? $row['temps_dintervention'] : null,
                'scheduled_date' => $scheduledDate,
                'completed_date' => $isClosed ? ($scheduledDate ?? now()) : null,
                'resolution_notes' => $row['detail_sur_lintervention'] ?? null,
                'is_validated' => $isClosed,
                'requested_by_connection_id' => $connection->id ?? null,
                'region_id' => $region->id ?? $connection->region_id ?? null,
                'zone_id' => $zone->id ?? $connection->zone_id ?? null,
                'assignable_id' => $assignee->id ?? null,
                'assignable_type' => $assignee ? User::class : null,
            ]
        );

        // 5. Création ou Mise à jour de l'Activité (Si l'intervention existe)
        if ($interventionRequest) {
            $this->syncActivity($interventionRequest, $row, $assignee);
        }

        return $interventionRequest;
    }

    /**
     * Gère la synchronisation de l'activité liée
     */
    private function syncActivity($intervention, $row, $assignee)
    {
        $activityTitle = 'Intervention: ' . $intervention->title;

        // Titre plus explicite si client identifié
        if ($intervention->requestedByConnection) {
            $activityTitle = "Inter Cli {$intervention->requestedByConnection->customer_code} - {$intervention->title}";
        }

        Activity::updateOrCreate(
            ['intervention_request_id' => $intervention->id],
            [
                'title' => $activityTitle,
                'status' => $intervention->status,
                'region_id' => $intervention->region_id,
                'zone_id' => $intervention->zone_id,
                'assignable_id' => $intervention->assignable_id,
                'assignable_type' => $intervention->assignable_type,
                'actual_start_time' => $intervention->scheduled_date,
                'actual_end_time' => $intervention->completed_date,
                'user_id' => auth()->id() ?? $assignee->id ?? null,
                'problem_resolution_description' => "Import auto. Description originale: " . ($row['plainte_soumise'] ?? 'N/A'),
            ]
        );
    }

    private function findZone($zoneString)
    {
        if (empty($zoneString)) return null;
        preg_match('/\/([^\)]+)/', $zoneString, $matches);
        $zoneName = trim($matches[1] ?? $zoneString);
        return Zone::where('title', 'like', '%' . $zoneName . '%')->first();
    }

    private function findAssignee($row)
    {
        if (!empty($row['nom_de_lintervenant'])) {
            return User::where('name', $row['nom_de_lintervenant'])->first();
        }
        if (!empty($row['code_du_technicien'])) {
            return User::where('employee_code', $row['code_du_technicien'])->first();
        }
        return null;
    }

    public function rules(): array
    {
        return [
            'id_plainte' => 'required',
            'plainte_soumise' => 'required',
            'code_client' => 'nullable|exists:connections,customer_code',
        ];
    }

    private function transformDate($value): ?string
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value)
                ? Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s')
                : Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
