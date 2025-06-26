<?php

declare(strict_types=1);

namespace App\Services;
use App\Contracts\Services\PackagistServiceContract;


class PackagistService implements PackagistServiceContract
{
    public function __construct(
        protected PackagistClient $client
    ) {}

    public function search(string $query, int $page = 1): array
    {
        $matches = [];
        do {
            $result = $this->client->search($query, $page);
            if (empty($result['results'])) {
                \Log::info('No results returned from Packagist search.');
                break;
            }
            foreach ($result['results'] as $pkg) {
                $name = $pkg['name'] ?? '';
                $tags = array_map('strtolower', $pkg['tags'] ?? []);
                // Match wn- prefix after author, or wintercms tag
                if (
                    !preg_match('#^[^/]+/wn-[^/]+#', $name)
                    && !str_starts_with($name, 'wintercms/')
                    && !str_starts_with($name, 'winter/')
                ) {
                    continue;
                }

                $matches[] = $pkg;
            }
            $page++;
        } while ($page <= ($result['total'] ?? 1));

        return $matches;
    }

    public function getPackageMeta(string $packageName): array
    {
        [$vendor, $package] = explode('/', $packageName, 2);
        $package = $this->client->getPackage($vendor, $package);

        return $package;
    }
}

