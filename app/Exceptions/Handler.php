<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        // Gérer les erreurs 404 et 403 pour les requêtes Inertia
        if ($request->wantsJson() && in_array($response->getStatusCode(), [404, 403])) {
            return Inertia::render('Errors/' . $response->getStatusCode(), [
                'message' => $e->getMessage(),
            ])->toResponse($request)->setStatusCode($response->getStatusCode());
        }

        // Fallback pour d'autres erreurs si nécessaire
        if ($request->wantsJson() && $response->getStatusCode() == 403) {
            return Inertia::render('Errors/403')->toResponse($request)->setStatusCode(403);
        }

        return $response;
    }
}
