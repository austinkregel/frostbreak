<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

/**
 * Core Update Controller Backend Controller
 */
class CoreUpdateController extends Controller
{
    public function handle(Request $request)
    {
        // This is expected to be the current git hash of the Backend
        $coreHash = $request->get('core');
        // This is a serialized PHP array of plugins and their versions; their plugins are referenced by their code, not their name
        $plugins = $request->get('plugins', []);
        // This is a serialized PHP array of themes and their versions; their themes are referenced by their code, not their name
        $themes = $request->get('themes', []);
        // This is the build number of the Backend; if not provided, it defaults to '1.0.0'
        $build = $request->get('build', '1.0.0');
        // This is a boolean indicating whether to force the update check; defaults to false
        $force = $request->get('force', false);

        $actualPlugins = unserialize($plugins);




        return response([]);
        return response()->json([
            'core' => [
                'hash' => 'my-new-hash',
                'build' => 4182,
                'old_build' => 'my-old-hash',
                'updates' => [
                    4182 => 'Security and performance improvements',
                    4181 => 'Bug fixes and enhancements',
                    4180 => 'Initial release with basic features',
                ],
            ],
            'plugins' => [
                 'code' => [
                     'name' => 'Plugin Name',
                     'version' => '1.0.0',
                     'hash' => 'my-plugin-hash',
                     'old_version' => false,
                     'icon' => false,
                     'is_frozen' => false,
                     'is_updatable' => true,
                 ],
            ],
            'themes' => [
                 'code' => [
                     'name' => 'Theme Name',
                     'version' => '1.0.0',
                     'hash' => 'my-theme-hash',
                     'old_version' => false,
                     'icon' => false,
                 ],
            ],
            'hasUpdates' => 1,
            'update' => 1,
        ]);
    }
}
