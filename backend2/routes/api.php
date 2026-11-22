<?php

use App\Http\Controllers\AdminAreaController;
use Illuminate\Support\Facades\Route;

Route::get('admin-areas/lookup', [AdminAreaController::class, 'lookup'])
    ->middleware('throttle:admin-area-lookup')
    ->name('admin-areas.lookup');

Route::get('admin-areas', [AdminAreaController::class, 'index'])
    ->name('admin-areas.index');
