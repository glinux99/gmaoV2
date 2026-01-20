<?php

namespace App\Imports;

use App\Models\Keypad;
use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class KeypadImport implements ToModel, WithHeadingRow, WithValidation
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
        $keypad = Keypad::where('serial_number', $serialNumber)->first();

        // Logique pour déterminer le fabricant et le modèle
        $manufacturer = null;
        $model = null;
        if (str_starts_with($serialNumber, '2')) {
            $manufacturer = 'INHEMTER';
            $model = 'INHEMTER';
        } elseif (str_starts_with($serialNumber, '09') || str_starts_with($serialNumber, '9') || str_starts_with($serialNumber, '08')) {
            $manufacturer = 'Landis+Gyr';
            $model = 'Landis+Gyr';
        } elseif (str_starts_with($serialNumber, '01') || str_starts_with($serialNumber, '1')) {
            $manufacturer = 'Ningbo';
            $model = 'Ningbo';
        }

        if ($keypad) {
            // Le clavier existe, on vérifie si un transfert est nécessaire
            if ($this->destinationRegionId && $keypad->region_id != $this->destinationRegionId) {
                $sourceRegionId = $keypad->region_id;
                // On met aussi à jour le fabricant/modèle lors du transfert si non défini
                $keypad->update(['region_id' => $this->destinationRegionId, 'status' => 'available', 'manufacturer' => $keypad->manufacturer ?? $manufacturer, 'model' => $keypad->model ?? $model]);

                StockMovement::create([
                    'movable_type' => Keypad::class,
                    'movable_id' => $keypad->id,
                    'type' => 'transfer',
                    'quantity' => 1,
                    'source_region_id' => $sourceRegionId,
                    'destination_region_id' => $this->destinationRegionId,
                    'user_id' => Auth::id(),
                    'date' => now(),
                    'notes' => 'Transfert automatique de clavier via importation.',
                ]);
                $this->summary['transferred']++;
            }
            return $keypad;
        } else {
            // Le clavier n'existe pas, on le crée
            $this->summary['created']++;
            return new Keypad([
                'serial_number' => $serialNumber,
                'status' => 'available',
                'manufacturer' => $manufacturer,
                'model' => $model,
                'region_id' => $this->destinationRegionId,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'serial_number' => 'required|string|max:255',
        ];
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
