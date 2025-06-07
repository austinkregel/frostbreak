<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Package;
use App\Services\PackagistClient;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TestPackagistCommand extends Command
{

    protected $name = 'kregel:test-packagist';
    protected $description = 'Test searching and fetching metadata from Packagist.';

    public function handle()
    {
        $client = new PackagistClient();
        $query = $this->ask('Enter search query for Packagist (default: wintercms)') ?: 'wintercms';
        $page = 1;
        $matches = [];
        do {
            $result = $client->search($query, $page);
            if (empty($result['results'])) {
                \Log::info('No results returned from Packagist search.');
                break;
            }
            foreach ($result['results'] as $pkg) {
                $name = $pkg['name'] ?? '';
                $desc = strtolower($pkg['description'] ?? '');
                $tags = array_map('strtolower', $pkg['tags'] ?? []);
                // Match wn- prefix after author
                if (preg_match('#^[^/]+/wn-[^/]+#', $name)) {
                    \Log::info('Package matches wn- prefix after author: ' . $name);
                    $matches[] = $pkg;
                    continue;
                }
                // Match wintercms tag
                if (in_array('wintercms', $tags)) {
                    \Log::info('Package matches wintercms tag: ' . $name);
                    $matches[] = $pkg;
                    continue;
                }
                // Match description
                if (strpos($desc, 'winter cms') !== false || strpos($desc, 'wintercms') !== false) {
                    \Log::info('Package matches winter cms or wintercms in description: ' . $name);
                    $matches[] = $pkg;
                    continue;
                }
                // Match octobercms tag
                if (in_array('octobercms', $tags)) {
                    \Log::info('Package matches octobercms tag: ' . $name);
                    $matches[] = $pkg;
                    continue;
                }
                // Match description
                if (strpos($desc, 'october cms') !== false || strpos($desc, 'octobercms') !== false) {
                    \Log::info('Package matches winter cms or octobercms in description: ' . $name);
                    $matches[] = $pkg;
                    continue;
                }
            }
            $page++;
        } while (!empty($result['results']) && $page <= ($result['total'] ?? 1));
        $filesystem = new Filesystem();
        $this->info('Filtered WinterCMS/OctoberCMS plugin packages:');
        foreach ($matches as $pkg) {
            $this->line($pkg['name'] . ' - ' . ($pkg['description'] ?? ''));
            $filesystem->makeDirectory($versionCache = storage_path('packages/'.$pkg['name']), 0755, true, true);

            // Optionally fetch and display metadata
            if (strpos($pkg['name'], '/') !== false) {
                if (str_starts_with($pkg['name'], 'october/')) {
                    $this->warn('Skipping OctoberCMS package: ' . $pkg['name']);
                    continue;
                }

                if (str_starts_with($pkg['name'], 'nuts-agency/')) {
                    $this->warn('Skipping nuts-agency package: ' . $pkg['name']);
                    continue;
                }


                [$vendor, $package] = explode('/', $pkg['name'], 2);
                $meta = cache()->remember($package, now()->addMinutes(40), fn() => $client->getPackage($vendor, $package));
                if (empty($meta)) {
                    \Log::warning('No metadata found for package: ' . $pkg['name']);
                    continue;
                }

                $versionsSortedByTime = collect($meta['package']['versions'] ?? [])
                    ->sortByDesc(fn($version) => Carbon::parse($version['time'] ?? '1970-01-01 00:00:00'))
                    ->values()
                    ->all();

                $latestVersion = Arr::first($versionsSortedByTime);

                $type = $this->getTypeFromPackage($latestVersion);
                $this->line('  Latest version: ' . ($latestVersion['version'] ?? 'n/a'));
                \Log::info('Fetched metadata for package: ' . $pkg['name'], $meta);
                // Store in Package model
                // We need the code to be Vendor.Package formatted; we should be able to modify the PSR-4 autoloading to use this format
                $codeish = Arr::first(array_keys($latestVersion['autoload']['psr-4'] ?? []));
                if (empty($codeish) && isset($latestVersion['name'])) {
                    $codeish = Str::before($latestVersion['name'], ':');
                    $codeish = str_replace('\\', '.', trim($codeish, '\\'));
                } else {
                    $codeish = str_replace('\\', '.', trim($codeish, '\\'));
                }
                /** @var Package $packageModel */
                $packageModel = Package::updateOrCreate(
                    [
                        'name' => $pkg['name'],
                    ],
                    [
                        'description' => $pkg['description'] ?? null,
                        'image' => null, // No image in Packagist, placeholder
                        'author' => $vendor ?? null,
                        'code' => $codeish,
                        'demo_url' => $meta['package']['homepage'] ?? null,
                        'product_url' => $meta['package']['repository'] ?? null,
                        'packagist_url' => $pkg['url'] ?? null,
                        'repository_url' => $pkg['repository'] ?? null,
                        'favers' => $pkg['favers'] ?? 0,
                        'downloads' => $pkg['downloads'] ?? 0,
                        'git_stars' => $meta['package']['github_stars'] ?? 0,
                        'git_forks' => $meta['package']['github_forks'] ?? 0,
                        'git_watchers' => $meta['package']['github_watchers'] ?? 0,
                        'last_updated_at' => $latestVersion['time'] ?? null,
                        'abandoned' => isset($meta['package']['abandoned']) ?? false,
                        'keywords' => array_values(array_unique(array_merge($latestVersion['keywords'] ?? [], array_filter([$type])))),
                        'needs_additional_processing' => false,
                    ]
                );

                if (!str_contains($packageModel->code, '.')) {
                    $packageModel->update(['needs_additional_processing' => true]);
                }

                $versions = array_values($meta['package']['versions'] ?? []);
                foreach ($versions as $version) {
                     if (isset($version['version'])) {
                         $hash = cache()->rememberForever('hash'.$version['dist']['url'], fn () => md5(Http::withHeaders([
                            'User-Agent' => 'Kregel/Marketplace',
                            'Authorization' => 'Basic ' . base64_encode(env('GITHUB_USERNAME').':'.env("GITHUB_TOKEN")),
                            'Accept' => 'application/vnd.github.v3+json',
                         ])->get($version['dist']['url'])
                         ->body()));


                        $v = $packageModel->versions()->firstOrCreate(
                            ['semantic_version' => $version['version']],
                            [
                                'requires' => $version['require'] ?? [],
                                'requires_dev' => $version['require-dev'] ?? [],
                                'suggests' => $version['suggest'] ?? [],
                                'provides' => $version['extra'] ?? [],
                                'conflicts' => $version['conflict'] ?? [],
                                'replaces' => $version['replace'] ?? [],
                                'tags' => $version['keywords'] ?? [],
                                'installation_commands' => $version['extra']['installation-commands'] ?? [],
                                'hash' => $hash,
                                'license' => Arr::first($version['license'] ?? []) ?? 'unlicensed (closed source)',
                                'description' => $version['description'] ?? null,
                                'released_at' => $version['time'] ?? null,
                                'dist_url' => $version['dist']['url'] ?? null,
                            ],
                        );

                        $v->fill([
                            'requires' => $version['require'] ?? [],
                            'requires_dev' => $version['require-dev'] ?? [],
                            'suggests' => $version['suggest'] ?? [],
                            'provides' => $version['extra'] ?? [],
                            'conflicts' => $version['conflict'] ?? [],
                            'replaces' => $version['replace'] ?? [],
                            'tags' => $version['keywords'] ?? [],
                            'installation_commands' => $version['extra']['installation-commands'] ?? [],
                            'hash' => $hash,
                            'license' => Arr::first($version['license'] ?? []) ?? 'unlicensed (closed source)',
                            'description' => $version['description'] ?? null,
                            'released_at' => $version['time'] ?? null,
                            'dist_url' => $version['dist']['url'] ?? null,
                        ]);
                        if ($v->isDirty()) {
                            $v->save();
                        }
                        if ($v->wasRecentlyCreated) {
                            $this->warn(' [*] Created new version: ' . $v->semantic_version . ' for package: ' . $pkg['name']);
                        } else {
                            $this->warn(' [*] Updated existing version: ' . $v->semantic_version . ' for package: ' . $pkg['name']);
                        }

                        if ($latestVersion['source']['reference'] === $version['source']['reference']) {
                            $packageModel->latest_version_id = $v->id ?? null;
                            $packageModel->save();
                        }
                    } else {
                         dd($version);
                 }
                }

                \Log::info('Stored or updated package: ' . $pkg['name']);
            }
        }
    }

    protected function getTypeFromPackage(array|null $latestVersion)
    {
        if (empty($latestVersion)) {
            return null;
        }

        if (str_ends_with($latestVersion['name'], 'docs')) {
            return null;
        }


        if (str_ends_with($latestVersion['name'], 'plugin')) {
            return 'plugin';
        }

        if (str_ends_with($latestVersion['name'], 'theme')) {
            return 'theme';
        }
        if (str_contains($latestVersion['name'], 'storm')) {
            // Storm plugins technically do extend winterCMS
            return 'plugin';
        }

        if (str_starts_with($latestVersion['name'], 'wintercms/')) {
            return 'winter';
        }

        if (str_starts_with($latestVersion['name'], 'winter/')) {
            return 'winter';
        }
        if (str_starts_with($latestVersion['name'], 'octobercms/')) {
            return 'october';
        }
        if (str_starts_with($latestVersion['name'], 'october/')) {
            return 'october';
        }

        $composerType = $latestVersion['type'] ?? '';

        if (str_ends_with($composerType, 'plugin')) {
            return 'plugin';
        }

        if (str_ends_with($composerType, 'theme')) {
            return 'theme';
        }

        dd($latestVersion);
    }
}

