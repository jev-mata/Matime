<?php

use Illuminate\Support\Facades\Route;
use Extensions\Timesheet\Http\Controllers\TimesheetController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
 */

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->prefix('v1')->group(function () {
    Route::apiResource('timesheet', TimesheetController::class)->names('timesheet');
});
Route::middleware([
    'auth:api', 
    'verified',
])->prefix('/time')->group(function () {
    Route::get('/', [TimesheetController::class, 'index']);
    Route::get('/show', [TimesheetController::class, 'show']);
    Route::get('/showAll', [TimesheetController::class, 'showAll']);
    Route::post('/submit', [TimesheetController::class, 'store']);
    Route::post('{timesheet}/approve', [TimesheetController::class, 'approve']);
    Route::post('{timesheet}/reject', [TimesheetController::class, 'reject']);
});
