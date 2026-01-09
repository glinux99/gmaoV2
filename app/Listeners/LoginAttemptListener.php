<?php

namespace App\Listeners;

use App\Models\Login;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LoginAttemptListener
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create the event listener.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle user login events.
     */
    public function handleLogin(LoginEvent $event): void
    {
        $login = Login::create([
            'user_id' => $event->user->id,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'login_at' => now(),
            'login_successful' => true,
        ]);

        // Stocker l'ID de l'enregistrement de connexion dans la session
        // pour le retrouver lors de la déconnexion.
        $this->request->session()->put('login_id', $login->id);
    }

    /**
     * Handle user failed login events.
     */
    public function handleFailedLogin(Failed $event): void
    {
        Login::create([
            'user_id' => $event->user?->id, // L'utilisateur peut être null si l'email n'existe pas
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'login_at' => now(),
            'login_successful' => false,
        ]);
    }

    /**
     * Gère les événements de déconnexion de l'utilisateur.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            // Au lieu de se fier à la session (qui est détruite avant cet événement),
            // on recherche la dernière connexion réussie de l'utilisateur qui n'a pas encore de date de déconnexion.
            $login = Login::where('user_id', $event->user->id)
                ->where('login_successful', true)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();

            if ($login) {
                $login->update([
                    'logout_at' => now(),
                    'session_duration' => now()->diffInSeconds($login->login_at),
                    'active_section' => $this->request->session()->get('active_section'), // Cette donnée peut aussi être perdue.
                ]);
            }
        }
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events): void
    {
        $events->listen(
            LoginEvent::class,
            [LoginAttemptListener::class, 'handleLogin']
        );

        $events->listen(
            Failed::class,
            [LoginAttemptListener::class, 'handleFailedLogin']
        );

        $events->listen(
            Logout::class,
            [LoginAttemptListener::class, 'handleLogout']
        );
    }
}
