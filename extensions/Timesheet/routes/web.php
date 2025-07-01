<?php

use App\Models\Member;
use Illuminate\Support\Facades\Route;
use Extensions\Timesheet\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->prefix('/time')->group(function () {


    Route::get('/approval', [TimesheetController::class, 'approval'])->name('approval.index');
    Route::get('/submit', [TimesheetController::class, 'index'])->name('timesheet.index');
    Route::get('/unsubmit', [TimesheetController::class, 'unSubmit'])->name('timesheet.unsubmit');
});