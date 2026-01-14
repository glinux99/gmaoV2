<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnginController;
use App\Http\Controllers\EquipmentCharacteristicController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentMovementController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ConnectionController;
use App\Models\User;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\KeypadController;
use App\Http\Controllers\InterventionRequestController;
use App\Http\Controllers\SettingController;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SparePartMovementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\InstructionTemplateController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportTemplateController;
use App\Http\Controllers\UnityController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\ZoneController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard', [
//         'users'         => (int) User::count(),
//         'roles'         => (int) Role::count(),
//         'permissions'   => (int) Permission::count(),
//     ]);
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');

    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');
    Route::post('/sessions/{session_id}', [SettingController::class, 'logoutSession'])->name('sessions.logout');
    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');
      // Déclarer les routes spécifiques AVANT les routes 'resource' pour éviter les conflits.
      Route::resources([
    'labels'=>LabelController::class,
    'unities'=> UnityController::class,
    'engins'=> EnginController::class,
    'regions'=> RegionController::class,
    'technicians' => TechnicianController::class,
    'teams'=> TeamController::class,
    'spare-parts'=> SparePartController ::class,
    'spare-part-movements'=> SparePartMovementController ::class,
    'equipments'=> EquipmentController::class,
    'equipment-movements'=> EquipmentMovementController ::class,
    'equipment-types'=> EquipmentTypeController::class,
    'equipment-characteristics'=> EquipmentCharacteristicController ::class,
    'maintenances'=> MaintenanceController::class,
    'tasks'=>TaskController::class,
    'activities'=> ActivityController::class,
    'agenda'=> AgendaController::class,
    'dashboard'=> DashboardController::class,
    'employees' => EmployeeController::class,
    'leaves' => LeaveController::class,
    'stock-movements' => StockMovementController::class,
    'payroll' => PaymentController::class,
    'expenses' => ExpensesController::class,
    'connections' => ConnectionController::class,
    'interventions' => InterventionRequestController::class,
    'reports' => ReportController::class,
    'instruction-templates' => InstructionTemplateController::class, // Utilise maintenant les routes resource (index, store, update, destroy)
    'report-templates' => ReportTemplateController::class,
    'networks' => NetworkController::class,
    'analytics' => AnalyticController::class,
    'meters' => MeterController::class,
    'keypads' => KeypadController::class,
    'zones'=> ZoneController::class,
    'roles'=> RoleController::class,
    'permissions'=> PermissionController::class,
    'users' =>UserController::class
  ]);

  Route::get('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
  Route::get('/users/leave-impersonate', [UserController::class, 'leaveImpersonate'])->name('users.leave-impersonate');

  Route::put('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.updateProfile');
  Route::put('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.updatePassword');
  Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

  Route::post('/activities/bulk-store', [ActivityController::class, 'bulkStore'])->name('activities.bulkStore');
  Route::put('/expenses/{expense}/status', [ExpensesController::class, 'updateStatus'])->name('expenses.updateStatus');
  Route::put('/leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.updateStatus');
  Route::put('/expenses/group-status', [ExpensesController::class, 'updateGroupStatus'])->name('expenses.updateGroupStatus');
Route::post('zones/bulk-destroy', [ZoneController::class, 'bulkDestroy'])->name('zones.bulkDestroy');

  Route::post('/connections/import', [ConnectionController::class, 'import'])->name('connections.import');
  Route::post('/meters/bulk-transfer', [MeterController::class, 'bulkTransfer'])->name('meters.bulk-transfer');
  Route::post('/keypads/bulk-transfer', [KeypadController::class, 'bulkTransfer'])->name('keypads.bulk-transfer');
Route::post('/equipments/bulk-destroy', [EquipmentController::class, 'bulkDestroy'])->name('equipments.bulkdestroy');
Route::put('/equipments/{equipment}/update-quantity', [EquipmentController::class, 'updateQuantity'])->name('equipments.update-quantity');
Route::post('/interventions/bulk-destroy', [InterventionRequestController::class, 'bulkDestroy'])->name('interventions.bulkdestroy');
Route::put('/interventions/{intervention}/assign', [InterventionRequestController::class, 'assign'])->name('interventions.assign');
Route::put('/interventions/{intervention}/cancel', [InterventionRequestController::class, 'cancel'])->name('interventions.cancel');
Route::put('/interventions/{intervention}/validate', [InterventionRequestController::class, 'validateIntervention'])->name('interventions.validate');
Route::post('/reports/reorder', [ReportController::class, 'reorder'])->name('reports.reorder');
Route::get('quantum/models', [ReportController::class, 'getModels']);
Route::post('quantum/query', [ReportController::class, 'fetchData'])->name('quantum.query');
Route::post('/meters/import', [MeterController::class, 'import'])->name('meters.import');
Route::post('/interventions/import', [InterventionRequestController::class, 'import'])->name('interventions.import');


});

Route::group(['middleware' => ['web']], function () {
    Route::get('/auth/{provider}/redirect', [SocialiteController ::class, 'redirect'])->name('socialite.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('socialite.callback');
});

Route::get('/form', function () {
    return Inertia::render('SakaiForm');
});

Route::get('/button', function () {
    return Inertia::render('SakaiButton');
});

Route::get('/list', function () {
    return Inertia::render('SakaiList');
});


// ... autres routes




// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
