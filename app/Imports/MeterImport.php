<?php

namespace App\Imports;

use App\Models\Meter;
use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class MeterImport implements ToModel, WithHeadingRow, WithValidation
{
    private ?int $destinationRegionId;
    private array $summary = ['created' => 0, 'transferred' => 0, 'processed' => 0];

    public function __construct(?int $destinationRegionId)
    {
        $this->destinationRegionId = $destinationRegionId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $serialNumber = $row['serial_number'] ?? null;

        if (!$serialNumber) {
            return null; // Ignore la ligne si le numéro de série est manquant
        }

        $this->summary['processed']++;
        $meter = Meter::where('serial_number', $serialNumber)->first();

        // Logique pour déterminer le fabricant et le modèle
        $manufacturer = null;
        $model = null;
        if (str_starts_with($serialNumber, '3728')) {
            $manufacturer = 'INHEMTER';
            $model = 'INHEMTER';
        } elseif (str_starts_with($serialNumber, '07') || str_starts_with($serialNumber, '7')) {
            $manufacturer = 'Landis+Gyr';
            $model = 'Landis+Gyr';
        } elseif (str_starts_with($serialNumber, '4') || str_starts_with($serialNumber, '04')) {
            $manufacturer = 'Ningbo';
            $model = 'Ningbo';
        }

        if ($meter) {
            // Le compteur existe, on vérifie si un transfert est nécessaire
            if ($this->destinationRegionId && $meter->region_id != $this->destinationRegionId) {
                $sourceRegionId = $meter->region_id;
                // On met aussi à jour le fabricant/modèle lors du transfert si non défini
                $meter->update(['region_id' => $this->destinationRegionId, 'status' => 'in_stock', 'manufacturer' => $meter->manufacturer ?? $manufacturer, 'model' => $meter->model ?? $model, 'type' => 'prepaid']);

                StockMovement::create([
                    'movable_type' => Meter::class,
                    'movable_id' => $meter->id,
                    'type' => 'transfer',
                    'quantity' => 1,
                    'source_region_id' => $sourceRegionId,
                    'destination_region_id' => $this->destinationRegionId,
                    'user_id' => Auth::id(),
                    'date' => now(),
                    'notes' => 'Transfert automatique via importation de fichier.',
                ]);
                $this->summary['transferred']++;
            }
            return $meter;
        } else {
            // Le compteur n'existe pas, on le crée
            $this->summary['created']++;
            return new Meter([
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
                'type' => 'prepaid', // Type par défaut
                'manufacturer' => $manufacturer,
                'model' => $model,
                'region_id' => $this->destinationRegionId,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'serial_number' => 'required|max:255',
        ];
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
