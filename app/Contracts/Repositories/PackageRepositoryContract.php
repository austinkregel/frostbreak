<?php

namespace App\Contracts\Repositories;

use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PackageRepositoryContract
{
    public function updateOrCreate(array $attributes, array $values = []): Package;
    public function syncPackages(array $packages, bool $sync = false): array;
    public function syncPackageAndQueueRepackageZip(array $pkg): array;

    public function findByCode(string $code): ?Package;
    public function findByComposerPackage(string $package): ?Package;
    public function findThemeByCode(string $code): ?Package;

    public function findPluginByCode(string $code): ?Package;

    public function findModuleByCode(string $code): ?Package;

    public function searchByCode(string $code, int $limit = 15, int $page = 1): LengthAwarePaginator;
    public function searchByPackageName(string $composerName, int $limit = 15, int $page = 1): LengthAwarePaginator;
    public function findSomePopular(int $limit = 15): LengthAwarePaginator;

    public function findAllPackageDetails(array $packageNames): Collection;

}
