<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnginController;
use App\Http\Controllers\EquipmentCharacteristicController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentMovementController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DonationController;
use App\Models\HeroSlide;
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
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SparePartMovementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\HeroSlideController;
use App\Http\Controllers\InstructionTemplateController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportTemplateController;
use App\Http\Controllers\UnityController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransformerController;
use App\Models\FaqItem;
use App\Models\Initiative;
use App\Models\Partner;
use App\Models\Post;
use App\Models\project;
use App\Models\Province;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\VolunteerController;
use App\Models\Setting;

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

Route::get('/conditions-generales', function () {
    return Inertia::render('Public/ConditionsGenerales');
})->name('cgu');
Route::post('/donations', [DonationController::class, 'store'])->name('donations.store');
 Route::get('/activites', [PostController::class, 'activities'])->name('activites.activities');
Route::get('activites/{slug}', [PublicController::class, 'activityDetails']);
Route::get('contact', [PublicController::class, 'contact']);
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/api/posts/{post}/comment', [PublicController::class, 'storeComment']);
Route::get('/about',[PublicController::class, 'about'])->name('about');





// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard', [
//         'users'         => (int) User::count(),
//         'roles'         => (int) Role::count(),
//         'permissions'   => (int) Permission::count(),
//     ]);
// })->middleware(['auth', 'verified'])->name('dashboard');
Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
     Route::resource('hero-slides', HeroSlideController::class)->except(['show', 'edit', 'create']);
    Route::post('hero-slides/reorder', [HeroSlideController::class, 'reorder'])->name('hero-slides.reorder');
    Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
    Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
    Route::post('/partners/reorder', [PartnerController::class, 'reorder'])->name('partners.reorder');
});
Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    // Routes pour la gestion des dons (CRUD)
    Route::get('/donors', [DonationController::class, 'index'])->name('donors.index');
    Route::post('/donors', [DonationController::class, 'store'])->name('donors.store');
    Route::put('/donors/{donation}', [DonationController::class, 'update'])->name('donors.update');
    Route::delete('/donors/{donation}', [DonationController::class, 'destroy'])->name('donors.destroy');
    Route::patch('/donors/{donation}/mark-contacted', [DonationController::class, 'markContacted'])->name('donations.markContacted');
    Route::post('/donors/reorder', [DonationController::class, 'reorder'])->name('donors.reorder');
});
Route::middleware('auth:sanctum')->prefix('api/chat')->group(function () {
    // Conversations

    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::post('/conversations', [ChatController::class, 'createConversation']);
    Route::put('/conversations/{conversation}', [ChatController::class, 'updateConversation']);
    Route::delete('/conversations/{conversation}', [ChatController::class, 'deleteConversation']);
    Route::post('/conversations/{conversation}/read', [ChatController::class, 'markAsRead']); // ← AJOUTÉ
    // Messages
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::put('/messages/{message}', [ChatController::class, 'updateMessage']);
    Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);

    // Attachments
    Route::get('/attachments/{attachment}/download', [ChatController::class, 'downloadAttachment']);
    Route::delete('/attachments/{attachment}', [ChatController::class, 'deleteAttachment']);

    // Reactions
    Route::post('/messages/{message}/reactions', [ChatController::class, 'toggleReaction']);

    // Gestion des membres (AJOUTÉ)
    Route::get('/conversations/{conversation}/members', [ChatController::class, 'getMembers']);
    Route::post('/conversations/{conversation}/members', [ChatController::class, 'addMembers']);
    Route::delete('/conversations/{conversation}/members/{user}', [ChatController::class, 'removeMember']);
    Route::put('/conversations/{conversation}/members/{user}', [ChatController::class, 'updateMemberRole']);

    // Utilitaires
    Route::get('/search/users', [ChatController::class, 'searchUsers']);
    Route::get('/unread-count', [ChatController::class, 'getUnreadCount']);
});
Route::middleware(['auth', 'verified', 'redirect.visitor'])->group(function () {
        Route::post('/api/posts/{post}/like', [PublicController::class, 'toggleLike']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
   Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');

    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');
    Route::post('/sessions/{session_id}', [SettingController::class, 'logoutSession'])->name('sessions.logout');
    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');
      // Déclarer les routes spécifiques AVANT les routes 'resource' pour éviter les conflits.
      // NOUVEAU : Déplacer la route de recherche AVANT la route resource
      Route::get('/stock-movements/search-items', [StockMovementController::class, 'searchMovableItems'])->name('stock-movements.search-items');
    Route::get('/messages', [ChatController::class, 'index'])->name('chat.index');

    // Endpoints AJAX pour Inertia (retournent du JSON)
    // Route::prefix('chat')->group(function () {
    //     Route::get('/conversations', [ChatController::class, 'getConversations']);
    //     Route::post('/conversations', [ChatController::class, 'createConversation']);
    //     Route::put('/conversations/{conversation}', [ChatController::class, 'updateConversation']);
    //     Route::delete('/conversations/{conversation}', [ChatController::class, 'deleteConversation']);

    //     Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    //     Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    //     Route::put('/messages/{message}', [ChatController::class, 'updateMessage']);
    //     Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);

    //     Route::get('/attachments/{attachment}/download', [ChatController::class, 'downloadAttachment']);
    //     Route::delete('/attachments/{attachment}', [ChatController::class, 'deleteAttachment']);

    //     Route::post('/messages/{message}/reactions', [ChatController::class, 'toggleReaction']);
    //     Route::get('/search/users', [ChatController::class, 'searchUsers']);
    // });

Route::middleware('auth:sanctum')->group(function () {

    // Partenaires
    Route::prefix('admin/partners')->name('partners.')->group(function () {
        Route::get('/', [PartnerController::class, 'index'])->name('index');
        Route::post('/', [PartnerController::class, 'store'])->name('store');
        Route::put('/{partner}', [PartnerController::class, 'update'])->name('update');
        Route::delete('/{partner}', [PartnerController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [PartnerController::class, 'reorder'])->name('reorder');
    });

    // Initiatives
    Route::prefix('admin/initiatives')->name('initiatives.')->group(function () {
        Route::get('/', [InitiativeController::class, 'index'])->name('index');
        Route::post('/', [InitiativeController::class, 'store'])->name('store');
        Route::put('/{initiative}', [InitiativeController::class, 'update'])->name('update');
        Route::delete('/{initiative}', [InitiativeController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [InitiativeController::class, 'reorder'])->name('reorder');
    });

    // FAQ
    Route::prefix('admin/faq')->name('faqs.')->group(function () {
        // Route::get('/', [FaqController:class, 'index'])->name('index');
        // Route::post('/', [FaqController::class, 'store'])->name('store');
        // Route::put('/{faq}', [FaqController::class, 'update'])->name('update');
        // Route::delete('/{faq}', [FaqController::class, 'destroy'])->name('destroy');
        // Route::post('/reorder', [FaqController::class, 'reorder'])->name('reorder');
    });

    // Newsletters
    Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Page principale (avec onglets)
    Route::get('/newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');

    // Abonnés
    Route::post('/subscribers', [NewsletterController::class, 'subscriberStore'])->name('subscribers.store');
    Route::put('/subscribers/{subscriber}', [NewsletterController::class, 'subscriberUpdate'])->name('subscribers.update');
    Route::delete('/subscribers/{subscriber}', [NewsletterController::class, 'subscriberDestroy'])->name('subscribers.destroy');
    Route::post('/subscribers/import', [NewsletterController::class, 'importSubscribers'])->name('subscribers.import');
    Route::get('/subscribers/all', [NewsletterController::class, 'subscribersAll'])->name('subscribers.all');

    // Campagnes
    Route::post('/campaigns', [NewsletterController::class, 'campaignStore'])->name('campaigns.store');
    Route::put('/campaigns/{campaign}', [NewsletterController::class, 'campaignUpdate'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [NewsletterController::class, 'campaignDestroy'])->name('campaigns.destroy');
    Route::post('/campaigns/{campaign}/send', [NewsletterController::class, 'sendCampaign'])->name('campaigns.send');

    // Réponse aux emails
    Route::post('/emails/reply', [NewsletterController::class, 'replyToEmail'])->name('emails.reply');
});

    // Projets
    Route::prefix('admin/projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [ProjectController::class, 'reorder'])->name('reorder');
    });

    // Témoignages
    Route::prefix('admin/testimonials')->name('testimonials.')->group(function () {
        Route::get('/', [TestimonialController::class, 'index'])->name('index');
        Route::post('/', [TestimonialController::class, 'store'])->name('store');
        Route::put('/{testimonial}', [TestimonialController::class, 'update'])->name('update');
        Route::delete('/{testimonial}', [TestimonialController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [TestimonialController::class, 'reorder'])->name('reorder');
    });

    // Bénévoles
    Route::prefix('admin/volunteers')->name('volunteers.')->group(function () {
        Route::get('/', [VolunteerController::class, 'index'])->name('index');
        Route::post('/', [VolunteerController::class, 'store'])->name('store');
        Route::put('/{volunteer}', [VolunteerController::class, 'update'])->name('update');
        Route::delete('/{volunteer}', [VolunteerController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [VolunteerController::class, 'reorder'])->name('reorder');
    });

    // Paramètres
    Route::prefix('admin/settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/update', [SettingController::class, 'update'])->name('update');
    });});


      Route::resources([

    'tasks'=>TaskController::class,
    'activities'=> ActivityController::class,
    'agenda'=> AgendaController::class,
    'dashboard'=> DashboardController::class,
    'employees' => EmployeeController::class,
    'leaves' => LeaveController::class,
    'stock-movements' => StockMovementController::class,
    'payroll' => PaymentController::class,
    'roles'=> RoleController::class,
    'permissions'=> PermissionController::class,
    'users' =>UserController::class,
    'categories' => CategoryController::class,
    'tags'=> TagController::class,
    'teams' => TeamController::class,
    'posts'=>PostController::class,
    'documents'=> PageController::class,
    // 'messages'=>MessageController::class,
    'contacts'=>ContactController::class,
  ]);

  Route::put('duplicate', [PostController::class, 'duplicate'])->name('posts.duplicate');
    // routes/web.php
Route::post('/teams/reorder', [TeamController::class, 'reorder'])->name('teams.reorder');

  Route::get('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
  Route::get('/users/leave-impersonate', [UserController::class, 'leaveImpersonate'])->name('users.leave-impersonate');

  Route::put('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.updateProfile');
  Route::put('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.updatePassword');
  Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

});
Route::middleware('auth', 'verified')->group(function () {
    Route::get('visitor', [SocialiteController::class, 'visitor'])->name('socialite.visitor');
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
