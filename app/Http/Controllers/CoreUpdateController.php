<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Core Update Controller Backend Controller
 */
class CoreUpdateController extends Controller
{
    public const WINTER_STORM_PACKAGE_CODE = 'wintercms/winter';

    public function handle(Request $request)
    {
        // Core hash is used when we don't have a specific build number
        $coreHash = $request->get('core');
        $plugins = $request->get('plugins', []);
        $themes = $request->get('themes', []);
        $build = $request->get('build', '1.0.0');
        $force = $request->get('force', false);

        $actualPlugins = is_array($plugins) ? $plugins : @unserialize($plugins);
        $actualThemes = is_array($themes) ? $themes : @unserialize($themes);
        $projectId = $request->has('project') ? $request->get('project') : null;
        $project = $request->has('project') ? Project::firstWhere('license_id', $projectId):  null;
        // --- CORE (wintercms/winter) ---
        $corePackage = \App\Models\Package::where('name', static::WINTER_STORM_PACKAGE_CODE)->first();
        $coreVersions = $corePackage ? $corePackage->versions()
            ->where('semantic_version', 'not like', 'dev-%')
            ->where('semantic_version', 'not like', '%-dev')
            ->orderBy('released_at')
            ->get() : collect();
        $coreCurrentVersion = $build;
        $coreLatest = $coreVersions->last(); // latest by released_at
        $coreUpdates = [];
        $coreHasUpdate = false;
        $newerVersions = $coreVersions->filter(function($v) use ($coreCurrentVersion, $force) {
            return $force || version_compare($v->semantic_version, $coreCurrentVersion, '>');
        });
        if ($newerVersions->count() > 0) {
            $coreHasUpdate = true;
            foreach ($newerVersions as $v) {
                $coreUpdates[$v->semantic_version] = $v->description;
            }
        }

        // --- PLUGINS ---
        $pluginsOut = [];
        if (is_array($actualPlugins)) {
            foreach ($actualPlugins as $code => $clientVersion) {
                $package = \App\Models\Package::where('code', $code)
                    ->whereJsonContains('keywords', 'plugin')
                    ->first();
                info('Found package for plugin: ' . $code . ' - ' . ($package ? $package->name : 'not found'));

                if ($package) {
                    $latestStable = $package->versions()
                        ->where('semantic_version', 'not like', 'dev-%')
                        ->where('semantic_version', 'not like', '%-dev')
                        ->orderByDesc('released_at')
                        ->first();
                    $latest = $latestStable ?: $package->versions()
                        ->orderByDesc('released_at')
                        ->first();


                    $isUpdatable = $latest && ($force || version_compare($latest->semantic_version, $clientVersion, '>'));
                    info('Latest version for plugin ' . $code . ': ' . ($latest ? $latest->semantic_version : 'not found'));
                    $pluginsOut[$code] = [
                        'name' => $package->name,
                        'version' => $latest ? $latest->semantic_version : $clientVersion,
                        'hash' => $latest ? $latest->hash : null,
                        'old_version' => $isUpdatable ? $clientVersion : false,
                        'icon' => $package->image ?? false,
                        'is_frozen' => false,
                        'is_updatable' => $isUpdatable,
                    ];
                }
            }
        }

        if (isset($project)) {
            foreach ($project->plugins as $plugin) {
                $latestStable = $plugin->versions()
                    ->where('semantic_version', 'not like', 'dev-%')
                    ->where('semantic_version', 'not like', '%-dev')
                    ->orderByDesc('released_at')
                    ->first();
                $latest = $latestStable ?: $plugin->versions()
                    ->orderByDesc('released_at')
                    ->first();

                if (!isset($latest)) {
                    continue;
                }
                $pluginsOut[$plugin->code] = [
                    'name' => $plugin->name,
                    'version' => $latest ? $latest->semantic_version : $clientVersion,
                    'hash' => $latest ? $latest->hash : null,
                    'old_version' => false,
                    'icon' => $plugin->image ?? false,
                    'is_frozen' => false,
                    'is_updatable' => true,
                ];
            }
        }

        if (isset($project)) {
            foreach ($project->themes as $plugin) {
                $latestStable = $plugin->versions()
                    ->where('semantic_version', 'not like', 'dev-%')
                    ->where('semantic_version', 'not like', '%-dev')
                    ->orderByDesc('released_at')
                    ->first();
                $latest = $latestStable ?: $plugin->versions()
                    ->orderByDesc('released_at')
                    ->first();

                if (!isset($latest)) {
                    continue;
                }

                if (isset($actualPlugins[$plugin->name]) && version_compare($actualPlugins[$plugin->name], $latest?->semantic_version ?? '0.0.0', '>=')) {
                    // Plugin is already listed, skip it
                    continue;
                }


                $pluginsOut[$plugin->code] = [
                    'name' => $plugin->name,
                    'version' => $latest ? $latest->semantic_version : null,
                    'hash' => $latest ? $latest->hash : null,
                    'old_version' => false,
                    'icon' => $plugin->image ?? false,
                ];
            }
        }

        // --- THEMES ---
        $themesOut = [];
        if (is_array($actualThemes)) {
            foreach ($actualThemes as $code => $clientVersion) {
                $package = \App\Models\Package::where('code', $code)
                    ->whereJsonContains('keywords', 'theme')
                    ->first();
                info('Found package for theme: ' . $code . ' - ' . ($package ? $package->name : 'not found'));
                if ($package) {
                    $latest = $package->versions()->orderByDesc('released_at')->first();
                    $isUpdatable = $latest && ($force || version_compare($latest->semantic_version, $clientVersion, '>'));
                    $themesOut[$code] = [
                        'name' => $package->name,
                        'version' => $latest ? $latest->semantic_version : $clientVersion,
                        'hash' => $latest ? $latest->hash : null,
                        'old_version' => $isUpdatable ? $clientVersion : false,
                        'icon' => $package->image ?? false,
                    ];
                }
            }
        }
        $response = [
            'core' => [
                'hash' => $coreLatest ? $coreLatest->hash : null,
                'build' => $coreLatest?->semantic_version ?? null,
                'old_build' => $coreCurrentVersion,
                'updates' => $coreUpdates,
            ],
            'plugins' => $pluginsOut,
            'themes' => $themesOut,
            'hasUpdates' => $coreHasUpdate || collect($pluginsOut)->where('is_updatable', true)->isNotEmpty() || !empty($themesOut),
            'update' => $coreHasUpdate || collect($pluginsOut)->where('is_updatable', true)->isNotEmpty() || !empty($themesOut),
        ];

        return response()->json($response);
    }

    public function get(Request $request)
    {
        $packageName = $request->get('name');
        $package = Package::query()
            ->where('name', 'wintercms/winter')
            ->firstOrFail();

        $latestVersion = $package->versions()
            ->where('semantic_version', 'not like', 'dev-%')
            ->where('semantic_version', 'not like', '%-dev')
            ->orderByDesc('released_at')
            ->first();

        abort_if(empty($latestVersion->dist_url), 404, 'No distribution URL found for this package.');
        $contentType = 'application/octet-stream';
        $contentDisposition = 'attachment; filename="' . $package->name . '-' . Str::slug($latestVersion->semantic_version) . '.zip"';

        $responseDownload = Storage::disk('packages')->get($latestVersion->getCacheLocation());

        return response($responseDownload, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }
}
