<?php

use Illuminate\Support\Facades\Route;
use App\Models\Package;
use App\Http\Controllers\Packages;
use App\Http\Controllers\CoreUpdateController;
use App\Http\Controllers\MarketplaceProxyController;
use App\Http\Controllers\Themes;
use App\Http\Controllers\Versions;

// API routes for packages
Route::withoutMiddleware([
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
])
    ->prefix('marketplace')
    ->group(function () {
    // Plugin Endpoints
    Route::post('/plugin/detail', [Packages::class, 'detail'])->name('kregel.root.plugin.detail');
    Route::post('/plugin/details', [Packages::class, 'details'])->name('kregel.root.plugin.details');
    Route::post('/plugin/popular', [Packages::class, 'popular'])->name('kregel.root.plugin.popular');
    Route::post('/plugin/search', [Packages::class, 'search'])->name('kregel.root.plugin.search');
    Route::post('/plugin/get', [Packages::class, 'package'])->name('kregel.root.plugin.package');

    // Theme Endpoints
    Route::post('/theme/detail', [Themes::class, 'detail'])->name('kregel.root.theme.detail');
    Route::post('/theme/get', [Themes::class, 'details'])->name('kregel.root.theme.get');
    Route::post('/theme/details', [Themes::class, 'details'])->name('kregel.root.theme.details');
    Route::post('/theme/popular', [Themes::class, 'popular'])->name('kregel.root.theme.popular');
    Route::post('/theme/search', [Themes::class, 'search'])->name('kregel.root.theme.search');

    // Core and Project Endpoints
    Route::post('/core/update', [CoreUpdateController::class, 'handle'])->name('kregel.root.core.update');
    Route::post('/project/detail', [\App\Http\Controllers\Projects::class, 'detail'])->name('kregel.root.project.detail');

    Route::get('/changelog/{branch?}', [\App\Http\Controllers\CoreChangelogController::class, 'changelog'])
        ->name('kregel.root.changelog');
});

//Route::get('/')

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Projects::class, 'index'])->name('dashboard');
    Route::post('/projects', [\App\Http\Controllers\Projects::class, 'store'])->name('projects.store');
    Route::get('/project/{project}', [\App\Http\Controllers\Projects::class, 'show'])->name('project.show');
    Route::post('/project/{project}/add-plugin', [\App\Http\Controllers\Projects::class, 'addPlugin'])->name('project.add-plugin');
    Route::post('/project/{project}/add-theme', [\App\Http\Controllers\Projects::class, 'addTheme'])->name('project.add-theme');
});
