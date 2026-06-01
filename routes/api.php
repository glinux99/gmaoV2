<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\ActivityApiController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionRequestController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TransformerController;
use App\Http\Controllers\UserController;
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
Route::put('/interventions/{intervention}/validate', [InterventionRequestController::class, 'validateIntervention'])->name('interventions.validate');
Route::get('quantum/models', [ReportController::class, 'getModels']);
Route::get('quantumx/models', [ReportController::class, 'fetchData']);


Route::post('/login', [ApiAuthController::class, 'login']);
// Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
//     // vos routes...
//        Route::get('/conversations', [ChatController::class, 'getConversations']);
//         Route::post('/conversations', [ChatController::class, 'createConversation']);
//         Route::put('/conversations/{conversation}', [ChatController::class, 'updateConversation']);
//         Route::delete('/conversations/{conversation}', [ChatController::class, 'deleteConversation']);

//         Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
//         Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
//         Route::put('/messages/{message}', [ChatController::class, 'updateMessage']);
//         Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);

//         Route::get('/attachments/{attachment}/download', [ChatController::class, 'downloadAttachment']);
//         Route::delete('/attachments/{attachment}', [ChatController::class, 'deleteAttachment']);

//         Route::post('/messages/{message}/reactions', [ChatController::class, 'toggleReaction']);
//         Route::get('/search/users', [ChatController::class, 'searchUsers']);
// });

Route::apiResources([
    "maintenancesx" =>ActivityApiController::class,
    'dashboard'=> DashboardApiController::class,
    "transformers" => TransformerController::class,
    'users'=>UserController::class,
    ]);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
