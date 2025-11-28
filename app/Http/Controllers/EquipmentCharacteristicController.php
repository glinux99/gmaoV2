<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCharacteristic;
use Illuminate\Http\Request;

class EquipmentCharacteristicController extends Controller
{
    public function destroy(EquipmentCharacteristic $equipmentCharacteristic)
    {
        // Optional: Add authorization check
        $equipmentCharacteristic->delete();

        return redirect()->back()->with('success', 'Caractéristique supprimée.');
    }
}
