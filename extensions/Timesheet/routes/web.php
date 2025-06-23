<?php

use Illuminate\Support\Facades\Route;
use Extensions\Timesheet\Http\Controllers\TimesheetController;

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
    Route::get('/submit', [TimesheetController::class, 'index'])->name('timesheet.index');
    Route::get('/unsubmit', [TimesheetController::class, 'unsubmit'])->name('timesheet.unsubmit');
});