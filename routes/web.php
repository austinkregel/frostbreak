<?php

use Illuminate\Support\Facades\Route;
use App\Models\Package;
use App\Http\Controllers\Packages;
use App\Http\Controllers\CoreUpdateController;
use App\Http\Controllers\Themes;
use App\Http\Controllers\Versions;

// API routes for packages
Route::withoutMiddleware([
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
])
    ->prefix('place')
    ->group(function () {
    // Plugin Endpoints
    Route::post('/plugin/detail', [Packages::class, 'detail'])->name('kregel.root.plugin.detail');
    Route::post('/plugin/details', [Packages::class, 'details'])->name('kregel.root.plugin.details');
    Route::post('/plugin/popular', [Packages::class, 'popular'])->name('kregel.root.plugin.popular');
    Route::post('/plugin/search', [Packages::class, 'search'])->name('kregel.root.plugin.search');
    Route::post('/plugin/get', [Packages::class, 'package'])->name('kregel.root.plugin.get');

    // Theme Endpoints
    Route::post('/theme/detail', [Themes::class, 'detail'])->name('kregel.root.theme.detail');
    Route::post('/theme/details', [Themes::class, 'details'])->name('kregel.root.theme.details');
    Route::post('/theme/popular', [Themes::class, 'popular'])->name('kregel.root.theme.popular');
    Route::post('/theme/search', [Themes::class, 'search'])->name('kregel.root.theme.search');
    Route::post('/theme/get', [Themes::class, 'theme'])->name('kregel.root.theme.get');

    // Core and Project Endpoints
    Route::post('/core/update', [CoreUpdateController::class, 'handle'])->name('kregel.root.core.update');
    Route::post('/project/detail', [\App\Http\Controllers\Projects::class, 'detail'])->name('kregel.root.project.detail');

    Route::get('/changelog/{branch?}', [\App\Http\Controllers\CoreChangelogController::class, 'changelog'])
        ->name('kregel.root.changelog');
});

// Public landing page using Inertia
Route::get('/', function () {
    return inertia('Landing', [
        'title' => 'Frostbreak Marketplace',
        'subtitle' => 'A public marketplace for WinterCMS plugins and themes',
        'notice' => '⚠️ This project is under heavy development. Not all features will work, but some will! Please report any issues you find.',
    ]);
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Projects::class, 'dashboard'])->name('dashboard');
    Route::post('/projects', [\App\Http\Controllers\Projects::class, 'store'])->name('projects.store');
    Route::get('/project/{project}', [\App\Http\Controllers\Projects::class, 'show'])->name('project.show');
    Route::post('/project/{project}/add-plugin', [\App\Http\Controllers\Projects::class, 'addPlugin'])->name('project.add-plugin');
    Route::post('/project/{project}/add-theme', [\App\Http\Controllers\Projects::class, 'addTheme'])->name('project.add-theme');
    Route::post('/project/{project}/remove-plugin', [\App\Http\Controllers\Projects::class, 'removePlugin'])->name('project.remove-plugin');
    Route::post('/project/{project}/remove-theme', [\App\Http\Controllers\Projects::class, 'removeTheme'])->name('project.remove-theme');
    Route::get('/projects', [\App\Http\Controllers\Projects::class, 'list'])->name('projects.list');
    Route::put('/projects/{project}', [\App\Http\Controllers\Projects::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [\App\Http\Controllers\Projects::class, 'destroy'])->name('projects.destroy');
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
});
