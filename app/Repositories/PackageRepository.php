<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Jobs\RepackageVersionInZipJob;
use App\Models\Package;
use App\Services\PackagistClient;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class PackageRepository implements PackageRepositoryContract
{
    public function __construct(
        protected FilesystemManager $manager,
        protected mixed $packageFilesystem,
        protected mixed $downloadFilesystem,
        protected mixed $archiveFilesystem
    ) {
        $this->packageFilesystem = $manager->disk('packages');
        $this->downloadFilesystem = $manager->disk('downloads');
        $this->archiveFilesystem = $manager->disk('archive');
    }

    public function updateOrCreate(array $attributes, array $values = []): Package
    {
        return Package::updateOrCreate($attributes, $values);
    }

    /**
     * Filter packages based on domain rules (customize as needed).
     * Logs filtered out or unknown cases for observability.
     */
    public function syncPackages(array $packages, bool $sync = false): array
    {
        foreach ($packages as $pkg) {
            $jobs = $this->syncPackageAndQueueRepackageZip($pkg);

            if (empty($jobs)) {
                continue;
            }

            info('Launching ' . count($jobs) . ' jobs for package: ' . $pkg['name']);
            if ($sync) {
                foreach ($jobs as $job) {
                    dispatch_sync($job);
                }
            } else {
                Bus::chain($jobs)->dispatch();
            }
        }

        return [];
    }

    public function syncPackageAndQueueRepackageZip(array $pkg): array
    {
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

        /** @var Package $packageModel */
        $packageModel = Package::firstOrCreate(
            [
                'name' => $pkg['name'],
            ],
            [
                'description' => $pkg['description'] ?? null,
                'image' => null, // No image in Packagist, placeholder
                'author' => $vendor ?? null,
                'code' => 'later',
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

        $lastUpdatedAt = $packageModel?->last_updated_at?->format('Y-m-d H:i') ?? null;

        if (
            isset($lastUpdatedAt)
            && !$packageModel->wasRecentlyCreated
            && $lastUpdatedAt === Carbon::parse($latestVersion['time'])->format('Y-m-d H:i')
        ) {
            info("No changes detected for package since our last update: {$packageModel->name}");
            return []; // No update needed
        }

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
                info("Updated version {$v->semantic_version} for package: {$packageModel->name}");
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
        if (str_ends_with($latestVersion['name'], 'module')) {
            return 'module';
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

        if (str_ends_with($composerType, 'module')) {
            return 'module';
        }

        if (str_ends_with($composerType, 'theme')) {
            return 'theme';
        }

        dd($latestVersion);
    }

    public function findByCode(string $code): ?Package
    {
        return Package::firstWhere('code', $code);
    }

    public function findThemeByCode(string $code): ?Package
    {
        return Package::query()
            ->whereJsonContains('keywords', 'theme')
            ->firstWhere('code', $code);
    }
    public function findPluginByCode(string $code): ?Package
    {
        return Package::query()
            ->whereJsonContains('keywords', 'plugin')
            ->firstWhere('code', $code);
    }

    public function findModuleByCode(string $code): ?Package
    {
        return Package::query()
            ->whereJsonContains('keywords', 'module')
            ->firstWhere('code', $code);
    }

    public function findByComposerPackage(string $package): ?Package
    {
        return Package::firstWhere('name', $package);
    }
    public function searchByCode(string $code, int $limit = 15, int $page = 1): LengthAwarePaginator
    {
        return Package::query()
            ->where('code', 'like', "%{$code}%")
            ->paginate(perPage: $limit, page: $page);
    }

    public function searchByPackageName(string $composerName, int $limit = 15, int $page = 1): LengthAwarePaginator
    {
        return Package::query()
            ->where('name', 'like', "%{$composerName}%")
            ->paginate(perPage: $limit, page: $page);
    }

    public function findSomePopular(int $limit = 15): LengthAwarePaginator
    {
        return Package::orderByDesc('downloads')
            ->where('needs_additional_processing', false)
            ->whereJsonContains('keywords', 'plugin')
            ->orderByDesc('favers')
            ->paginate($limit, page: 1);
    }

    public function findAllPackageDetails(array $packageNames): Collection
    {
        return Package::query()
            ->whereJsonContains('keywords', 'plugin')
            ->whereIn('code', $packageNames)
            ->where('needs_additional_processing', false)
            ->get();
    }
}
