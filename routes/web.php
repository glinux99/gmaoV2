<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\EnginController;
use App\Http\Controllers\EquipmentCharacteristicController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentMovementController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\SparePartController;
use App\Models\User;
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
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\UnityController;

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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'         => (int) User::count(),
        'roles'         => (int) Role::count(),
        'permissions'   => (int) Permission::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');

    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');

    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');

});
Route::get('/auth/{provider}/redirect', [SocialiteController ::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('socialite.callback');

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
  ]);

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
