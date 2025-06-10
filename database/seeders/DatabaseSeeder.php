<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);

            // --- WINTER PACKAGES FROM COMPOSER.JSON ---
            $winterPackages = [
                'winter/storm' => '0.0.1',
                'winter/wn-backend-module' => '0.0.1',
                'winter/wn-cms-module' => '0.0.1',
                'winter/wn-system-module' => '0.0.1',
                'winter/wn-user-plugin' => '0.0.1',
                'winter/wn-pages-plugin' => '0.0.1',
                'winter/wn-blog-plugin' => '0.0.1',
                'winter/wn-translate-plugin' => '0.0.1',
                'winter/wn-mail-module' => '0.0.1',
                'winter/wn-media-module' => '0.0.1',
                'winter/wn-editor-module' => '0.0.1',
            ];
            $allVersions = ['0.0.1', '0.1.0', '1.0.0', '2.0.0', '2.0.1', '2.1.0']; // 2.1.0 is > any in composer.json
            $createdPackages = [];
            foreach ($winterPackages as $name => $baseVersion) {
                $package = \App\Models\Package::create([
                    'name' => $name,
                    'description' => 'Seeded package for ' . $name,
                    'code' => $name,
                    'author' => 'Winter CMS',
                    'keywords' => ['winter', 'cms', 'plugin', 'module'],
                    'is_approved' => true,
                    'repository_url' => 'https://github.com/' . $name,
                    'packagist_url' => 'https://packagist.org/packages/' . $name,
                    'product_url' => 'https://wintercms.com/plugin/' . $name,
                    'downloads' => rand(1000, 10000),
                    'git_stars' => rand(1, 500),
                    'git_forks' => rand(1, 100),
                ]);
                $createdPackages[] = $package;
                $latestVersionId = null;
                foreach ($allVersions as $ver) {
                    $version = \App\Models\Version::create([
                        'package_id' => $package->id,
                        'semantic_version' => $ver,
                        'hash' => sha1($name . $ver),
                        'released_at' => now()->subDays(rand(1, 100)),
                        'dist_url' => 'https://github.com/' . $name . '/archive/refs/tags/v' . $ver . '.zip',
                        'license' => 'MIT',
                        'description' => 'Version ' . $ver . ' of ' . $name,
                        'requires' => json_encode([
                            'php' => '>=7.2',
                            'winter/storm' => '>=0.0.1',
                        ]),
                        'suggests' => json_encode([
                            'winter/wn-backend-module' => 'For backend functionality',
                            'winter/wn-cms-module' => 'For CMS features',
                        ]),
                        'requires_dev' => json_encode([
                            'phpunit/phpunit' => '^9.0',
                        ]),
                        'provides' => json_encode([
                            'winter/' . $name => $ver,
                        ]),
                        'conflicts' => json_encode([
                            'winter/wn-translate-plugin' => '>=0.0.1',
                        ]),
                        'replaces' => json_encode([
                            'winter/wn-legacy-plugin' => '>=0.0.1',
                        ]),
                        'tags' => json_encode(['winter', 'cms', 'plugin', 'module']),
                        'installation_commands' => json_encode([
                            'composer require ' . $name . ':' . $ver,
                        ]),
                    ]);
                    if (version_compare($ver, $latestVersionId ? $version->semantic_version : '0.0.0', '>')) {
                        $latestVersionId = $version->id;
                    }
                }
                $package->latest_version_id = $latestVersionId;
                $package->save();
            }

            // Seed projects for the admin user
            $projects = [
                $user->projects()->create([
                    'name' => 'Website Redesign',
                    'license_id' => Uuid::uuid4(),
                    'owner' => $user->name
                ]),
                $user->projects()->create([
                    'name' => 'Mobile App Backend',
                    'license_id' => Uuid::uuid4(),
                    'owner' => $user->name
                ]),
                $user->projects()->create([
                    'name' => 'Marketing Campaign',
                    'license_id' => Uuid::uuid4(),
                    'owner' => $user->name
                ]),
            ];

            // Attach random packages to projects
            foreach ($projects as $project) {
                $attachCount = rand(3, count($createdPackages));
                $toAttach = collect($createdPackages)->shuffle()->take($attachCount)->pluck('id');
                $project->packages()->attach($toAttach);
            }
        });
    }
}
