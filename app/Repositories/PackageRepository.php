<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Jobs\RepackageVersionInZipJob;
use App\Models\Package;
use App\Services\PackagistClient;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PackageRepository
{
    public function __construct(
        protected Filesystem $filesystem,
    ) {
    }

    public function updateOrCreate(array $attributes, array $values = []): Package
    {
        return Package::updateOrCreate($attributes, $values);
    }

    /**
     * Filter packages based on domain rules (customize as needed).
     * Logs filtered out or unknown cases for observability.
     */
    public function syncPackages(array $packages): array
    {
        foreach ($packages as $pkg) {
            $jobs = $this->syncPackageAndQueueRepackageZip($pkg);

            Bus::chain($jobs)->dispatch();
        }

        return [];
    }

    public function syncPackageAndQueueRepackageZip(array $pkg): array
    {
        $this->filesystem->makeDirectory($versionCache = storage_path('packages/'.$pkg['name']), 0755, true, true);

        [$vendor, $package] = explode('/', $pkg['name'], 2);
        $meta = app(PackagistClient::class)->getPackage($vendor, $package);
        if (empty($meta)) {
            \Log::warning('No metadata found for package: ' . $pkg['name']);
            return [];
        }

        $versionsSortedByTime = collect($meta['package']['versions'] ?? [])
            ->sortByDesc(fn($version) => Carbon::parse($version['time'] ?? '1970-01-01 00:00:00'))
            ->values()
            ->all();

        $latestVersion = Arr::first($versionsSortedByTime);

        $type = $this->getTypeFromPackage($latestVersion);
        \Log::info('Fetched metadata for package: ' . $pkg['name']);
        // Store in Package model
        // We need the code to be Vendor.Package formatted; we should be able to modify the PSR-4 autoloading to use this format
        $standardCode = Arr::first(array_keys($latestVersion['autoload']['psr-4'] ?? []));
        if (empty($standardCode) && isset($latestVersion['name'])) {
            $standardCode = Str::before($latestVersion['name'], ':');
        }

        // Remove any theme or plugin keywords from the list of keywords,  and then add our own indicator
        $keywords = array_values(
            array_unique(
                array_merge(
                    [$type],
                    array_filter(
                        $version['keywords'] ?? [],
                        fn ($keyword) => in_array($keyword, ['plugin', 'theme'])
                    )
                )
            )
        );

        $standardCode = str_replace(['\\', '/'], ['.', '.'], trim($standardCode, '\\'));
        /** @var Package $packageModel */
        $packageModel = Package::updateOrCreate(
            [
                'name' => $pkg['name'],
            ],
            [
                'description' => $pkg['description'] ?? null,
                'image' => null, // No image in Packagist, placeholder
                'author' => $vendor ?? null,
                'code' => $standardCode,
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
                'keywords' => $keywords,
                'needs_additional_processing' => false,
            ]
        );

        $versions = array_values($meta['package']['versions'] ?? []);
        $jobs = [];
        foreach ($versions as $version) {
            $v = $packageModel->versions()->firstOrCreate(
                ['semantic_version' => $version['version']],
                [
                    'requires' => $version['require'] ?? [],
                    'requires_dev' => $version['require-dev'] ?? [],
                    'suggests' => $version['suggest'] ?? [],
                    'provides' => $version['extra'] ?? [],
                    'conflicts' => $version['conflict'] ?? [],
                    'replaces' => $version['replace'] ?? [],
                    'tags' => $keywords,
                    'installation_commands' => $version['extra']['installation-commands'] ?? [],
                    'license' => Arr::first($version['license'] ?? []) ?? 'unlicensed (closed source)',
                    'description' => $version['description'] ?? null,
                    'released_at' => $version['time'] ?? null,
                    'dist_url' => $version['dist']['url'] ?? null,
                ],
            );

            $hasHadRecentChanges = isset($version['time']) && $v->released_at->isBefore(Carbon::parse($version['time']));

            if ($hasHadRecentChanges) {
                $v->fill([
                    'requires' => $version['require'] ?? [],
                    'requires_dev' => $version['require-dev'] ?? [],
                    'suggests' => $version['suggest'] ?? [],
                    'provides' => $version['extra'] ?? [],
                    'conflicts' => $version['conflict'] ?? [],
                    'replaces' => $version['replace'] ?? [],
                    'tags' => $keywords,
                    'installation_commands' => $version['extra']['installation-commands'] ?? [],
                    'license' => Arr::first($version['license'] ?? []) ?? 'unlicensed (closed source)',
                    'description' => $version['description'] ?? null,
                    'dist_url' => $version['dist']['url'] ?? null,
                    'released_at' => $version['time'],
                ]);
            }
            if ($v->isDirty()) {
                $v->save();
            }

            if ($latestVersion['source']['reference'] === $version['source']['reference']) {
                $packageModel->latest_version_id = $v->id ?? null;
                $packageModel->save();
            }

            // We only want to FORCE a download if the version we have was released before the packagist version.
            // Likely to be dev versions, new tags, or main branch changes.
            $jobs[] = new RepackageVersionInZipJob($packageModel, $v, force: $hasHadRecentChanges);
        }

        return $jobs;
    }

    /**
     * Determine if a package should be included (customize domain logic here).
     */
    protected function shouldIncludePackage(array $package): bool
    {
        // Add domain-specific rules as needed
        return true;
    }

    /**
     * Log unknown cases for observability.
     */
    public function logUnknownCase(array $context): void
    {
        Log::error('Unknown package case encountered', $context);
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

        if (str_ends_with($composerType, 'plugin') || str_ends_with($composerType, 'module')) {
            return 'plugin';
        }

        if (str_ends_with($composerType, 'theme')) {
            return 'theme';
        }

        dd($latestVersion);
    }

    protected function validateGithubCredentials(): void
    {
        if (empty(env('GITHUB_USERNAME')) || empty(env('GITHUB_TOKEN'))) {
            $this->error('Please set GITHUB_USERNAME and GITHUB_TOKEN in your .env file.');
            throw new \DomainException('Defined the GITHUB_USERNAME and GITHUB_TOKEN environment variables in your .env file.');
            exit(1);
        }
    }
}
