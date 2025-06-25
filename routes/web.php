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
    Route::post('/theme/get', [Packages::class, 'package'])->name('kregel.root.theme.get');

    // Core and Project Endpoints
    Route::post('/core/get', [CoreUpdateController::class, 'get'])->name('kregel.root.core.get');
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
        'notice' => '⚠️ This project is active development. Not all features will work, but most will! Please report any issues you find on github.',
    ]);
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Projects::class, 'dashboard'])->name('dashboard');
    Route::post('/projects', [\App\Http\Controllers\Projects::class, 'store'])->name('projects.store');
    Route::get('/project/{project:license_id}', [\App\Http\Controllers\Projects::class, 'show'])->name('project.show');
    Route::post('/project/{project:license_id}/add-plugin', [\App\Http\Controllers\Projects::class, 'addPlugin'])->name('project.add-plugin');
    Route::post('/project/{project:license_id}/add-theme', [\App\Http\Controllers\Projects::class, 'addTheme'])->name('project.add-theme');
    Route::post('/project/{project:license_id}/remove-plugin', [\App\Http\Controllers\Projects::class, 'removePlugin'])->name('project.remove-plugin');
    Route::post('/project/{project:license_id}/remove-theme', [\App\Http\Controllers\Projects::class, 'removeTheme'])->name('project.remove-theme');
    Route::get('/projects', [\App\Http\Controllers\Projects::class, 'list'])->name('projects.list');
    Route::put('/projects/{project:license_id}', [\App\Http\Controllers\Projects::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:license_id}', [\App\Http\Controllers\Projects::class, 'destroy'])->name('projects.destroy');
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
});
// We need to define our own 404 route so we can identify routes that aren't making their way to our app.
Route::fallback(function () {
    info('404 Not Found: ' . request()->getPathInfo() . ' - ' . request()->getMethod(), [
        'ip' => request()->ip(),
        'user_agent' => request()->header('User-Agent'),
        'path' => request()->getPathInfo(),
        'method' => request()->getMethod(),
    ]);
    return response()->json([
        'message' => 'Not Found',
        'status' => 404,
        'path' => request()->getPathInfo(),
        'method' => request()->getMethod(),
        'timestamp' => now()->toIso8601String(),
    ], 404);
})->name('not-found');
