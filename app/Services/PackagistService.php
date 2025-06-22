<?php

declare(strict_types=1);

namespace App\Services;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class PackagistService
{
    public function __construct(
        protected PackagistClient $client
    ) {}

    public function search(string $query): array
    {
        $page = 1;
        $matches = [];
        do {
            $result = cache()->remember('packagist-search.'.$query.$page, now()->addMinutes(30), fn () => $this->client->search($query, $page));
            if (empty($result['results'])) {
                \Log::info('No results returned from Packagist search.');
                break;
            }
            foreach ($result['results'] as $pkg) {
                $name = $pkg['name'] ?? '';
                $tags = array_map('strtolower', $pkg['tags'] ?? []);
                // Match wn- prefix after author, or wintercms tag
                if (!in_array('wintercms', $tags) || !preg_match('#^[^/]+/wn-[^/]+#', $name)) {
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

