<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    public function render($request, Throwable $e): Response|RedirectResponse
    {
        // $response = parent::render($request, $e);

        // if ($response->getStatusCode() === 419) {
        //     return back()->with([
        //         'message' => 'The page expired, please try again.',
        //     ]);
        // }

        // return $response;

        // Handle Inertia 419 CSRF token mismatch
        if ($e instanceof TokenMismatchException) {
            return back()->with('message', 'Your session has expired. Please try again.');
        }

        // 404 – Eloquent model not found
        if ($e instanceof ModelNotFoundException) {
            return Inertia::render('Notfound', ['status' => 404])
                ->toResponse($request)
                ->setStatusCode(404);
        }

        // Other HTTP exceptions (403, 429, 500 …)
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();   // not getCode()
            return Inertia::render('Notfound', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        }

        // Database
        if ($e instanceof QueryException) {
            $status = app()->isLocal() ? 500 : 503;
            $message = app()->isLocal()
                ? 'Database error occurred'
                : 'Service temporarily unavailable';

            return Inertia::render('Notfound', compact('status', 'message'))
                ->toResponse($request)
                ->setStatusCode($status);
        }

        // Fallback
        return parent::render($request, $e);
    }
}
