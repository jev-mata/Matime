<?php

declare(strict_types=1);

use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Models\Timesheet;
use Illuminate\Support\Facades\Auth;
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
    'web',
])->group(function (): void {
    Route::get('/invitations/{invitation}', [TeamInvitationController::class, 'showAcceptPage'])->name('invitations.accept');
    Route::post('/invitations/{invitation}', [TeamInvitationController::class, 'accept'])
        ->name('team.invitations.accept');
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
        $user = Auth::user();
        $currentOrgId = $user->currentOrganization->id;

        $organization = $user->organizations()
            ->where('organization_id', $currentOrgId)
            ->first();

        $role = $organization?->membership->role;

        if ($role === 'employee') {
            return redirect()->route('dashboard');
        }

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
    Route::delete('/teams/{team}', [TeamController::class, 'removeGroup']);
    Route::post('/teams/{team}/name/{name}', [TeamController::class, 'updateGroup']);

    Route::post('/teams/{teamid}/projects/{projectid}', [TeamController::class, 'assignTeam2Project']);

    Route::post('/teams/{team}/assign-project', [TeamController::class, 'assignProject']);
    Route::post('/get/current/org', [TeamController::class, 'getOrg']);
    Route::post('/teams/{team}/assign-members', [TeamController::class, 'assignMembers']);
    Route::name('approval.')->prefix('/approval')->group(static function (): void {

        Route::post('/permission', [TimesheetController::class, 'permission'])->name('permission');
        Route::get('/time-entries', [TimesheetController::class, 'getAllTimeEntries'])->name('all');
        Route::post('/submit', [TimesheetController::class, 'Submit'])->name('submit');
        Route::post('/unsubmit', [TimesheetController::class, 'Unsubmit'])->name('unsubmit');
        Route::get('/', [TimesheetController::class, 'approval'])->name('index');
        Route::get('/overview', [TimesheetController::class, 'ApprovalOverview'])->name('ApprovalOverview');


        Route::post('/approve', [TimesheetController::class, 'approve'])->name('approve');
        Route::post('/reject', [TimesheetController::class, 'reject'])->name('reject');

        Route::post('/withdraw', [TimesheetController::class, 'withdraw'])->name('withdraw');
        Route::post('/remind', [TimesheetController::class, 'remind'])->name('remind');
    });
    Route::name('approval.')->prefix('/timesheet')->group(static function (): void {
        Route::get('/unsubmit', [TimesheetController::class, 'unsubmitView'])->name('unsubmit.get');
        Route::post('/submit', [TimesheetController::class, 'store'])->name('submit.post');
        Route::post('/unsubmit', [TimesheetController::class, 'destroy'])->name('unsubmit.post');
    });
});
Route::fallback(function () {
    return Inertia::render('Error/404');
});
// Route::get('/team-invitation/view', [TeamInvitationController::class, 'showAcceptPage']);