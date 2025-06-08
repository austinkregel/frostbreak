<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

/**
 * Core Update Controller Backend Controller
 */
class CoreUpdateController extends Controller
{
    public const WINTER_STORM_PACKAGE_CODE = 'Winter.Storm';

    public function handle(Request $request)
    {
        $coreHash = $request->get('core');
        $plugins = $request->get('plugins', []);
        $themes = $request->get('themes', []);
        $build = $request->get('build', '1.0.0');
        $force = $request->get('force', false);

        $actualPlugins = is_array($plugins) ? $plugins : @unserialize($plugins);
        $actualThemes = is_array($themes) ? $themes : @unserialize($themes);

        // --- CORE (storm) ---
        $corePackage = \App\Models\Package::where('code', static::WINTER_STORM_PACKAGE_CODE)->first();
        $coreVersions = $corePackage ? $corePackage->versions()->orderBy('released_at')->get() : collect();
        $coreLatest = $coreVersions->last();
        $coreCurrentVersion = $build;
        $coreUpdates = [];
        $coreHasUpdate = false;
        $coreOldBuild = $coreCurrentVersion;
        if ($corePackage && $coreLatest) {
            // Find all versions newer than the current build
            $newerVersions = $coreVersions->filter(function($v) use ($coreCurrentVersion, $force) {
                return $force || version_compare($v->semantic_version, $coreCurrentVersion, '>');
            });
            if ($newerVersions->count() > 0) {
                $coreHasUpdate = true;
                foreach ($newerVersions as $v) {
                    $coreUpdates[$v->semantic_version] = $v->description;
                }
                $coreOldBuild = $coreCurrentVersion;
                $coreCurrentVersion = $coreLatest->semantic_version;
            }
        }

        // --- PLUGINS ---
        $pluginsOut = [];
        if (is_array($actualPlugins)) {
            foreach ($actualPlugins as $code => $clientVersion) {
                $package = \App\Models\Package::where('code', $code)
                    ->whereJsonContains('keywords', 'plugin')
                    ->first();
                if ($package) {
                    $latest = $package->versions()->orderByDesc('released_at')->first();
                    $isUpdatable = $latest && ($force || version_compare($latest->semantic_version, $clientVersion, '>'));
                    $pluginsOut[$code] = [
                        'name' => $package->name,
                        'version' => $latest ? $latest->semantic_version : $clientVersion,
                        'hash' => $latest ? $latest->hash : null,
                        'old_version' => $isUpdatable ? $clientVersion : false,
                        'icon' => $package->image ?? false,
                        'is_frozen' => false, // You can add logic for frozen plugins if needed
                        'is_updatable' => $isUpdatable,
                    ];
                }
            }
        }

        // --- THEMES ---
        $themesOut = [];
        if (is_array($actualThemes)) {
            foreach ($actualThemes as $code => $clientVersion) {
                $package = \App\Models\Package::where('code', $code)
                    ->whereJsonContains('keywords', 'theme')
                    ->first();
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

        return response()->json([
            'core' => [
                'hash' => $coreLatest ? $coreLatest->hash : null,
                'build' => $coreCurrentVersion,
                'old_build' => $coreOldBuild,
                'updates' => $coreUpdates,
            ],
            'plugins' => $pluginsOut,
            'themes' => $themesOut,
            'hasUpdates' => $coreHasUpdate || collect($pluginsOut)->where('is_updatable', true)->isNotEmpty() || !empty($themesOut),
            'update' => $coreHasUpdate || collect($pluginsOut)->where('is_updatable', true)->isNotEmpty() || !empty($themesOut),
        ]);
    }
}
