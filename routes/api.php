<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Projects as ProjectsController;

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::get('/projects/search', [ProjectsController::class, 'searchApi'])->name('api.projects.search');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
