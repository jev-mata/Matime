<?php

declare(strict_types=1);

use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

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

Route::get('/', [HomeController::class, 'index']);

Route::get('/shared-report', function () {
    return Inertia::render('SharedReport');
})->name('shared-report');
Route::middleware([
    'signed'
])->group(function (): void {
    Route::get('/invitations/{invitation}', [TeamInvitationController::class, 'showAcceptPage'])->name('invitations.accept');
    Route::post('/invitations/{invitation}', [TeamInvitationController::class, 'accept'])
        ->name('team-invitations.accept');
});
Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/time', function () {

        return Inertia::render('Time');
    })->name('time');

    Route::get('/reporting', function () {
        return Inertia::render('Reporting');
    })->name('reporting');

    Route::get('/reporting/detailed', function () {
        return Inertia::render('ReportingDetailed');
    })->name('reporting.detailed');

    Route::get('/reporting/shared', function () {
        return Inertia::render('ReportingShared');
    })->name('reporting.shared');

    Route::get('/projects', function () {
        return Inertia::render('Projects');
    })->name('projects');

    Route::get('/projects/{project}', function () {
        return Inertia::render('ProjectShow');
    })->name(name: 'projects.show');

    Route::get('/clients', function () {
        return Inertia::render('Clients');
    })->name('clients');

    Route::get('/members', function () {
        return Inertia::render('Members', [
            'availableRoles' => array_values(Jetstream::$roles),
        ]);
    })->name('members');

    Route::get('/tags', function () {
        return Inertia::render('Tags');
    })->name('tags');

    Route::get('/import', function () {
        return Inertia::render('Import');
    })->name('import');
    //Teams routes

    Route::prefix('organizations')->group(function () {
        Route::get('/teams', [TeamController::class, 'index']);
        Route::get('/teams/projects/{projectid}', [TeamController::class, 'show']);
        Route::get('/projects/teams/{projectid}', [TeamController::class, 'showowned']);
        Route::post('/teams', [TeamController::class, 'store']);
    });

    Route::delete('/teams/{team}/projects/{project}', [TeamController::class, 'removeProject']);
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);

    Route::post('/teams/{teamid}/projects/{projectid}', [TeamController::class, 'assignTeam2Project']);

    Route::post('/teams/{team}/assign-project', [TeamController::class, 'assignProject']);
    Route::post('/get/current/org', [TeamController::class, 'getOrg']);
    Route::post('/teams/{team}/assign-members', [TeamController::class, 'assignMembers']);

});

// Route::get('/team-invitation/view', [TeamInvitationController::class, 'showAcceptPage']);