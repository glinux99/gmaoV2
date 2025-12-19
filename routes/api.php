<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionRequestController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
 // Correction de la route API Resource

Route::post('/bulk-destroy', [EquipmentController ::class, 'bulkDestroy'])->name('equipments.bulkdestroy');
Route::post('/activities/bulk-store', [ActivityController::class, 'bulkStore'])->name('activities.bulkStore');


Route::apiResource("maintenancesx", InterventionRequestController::class);Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
