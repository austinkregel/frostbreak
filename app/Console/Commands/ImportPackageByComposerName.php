<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Contracts\Services\PackagistServiceContract;
use App\Jobs\RepackageVersionInZipJob;
use App\Models\Package;
use Illuminate\Console\Command;

class ImportPackageByComposerName extends Command
{
    protected $signature = 'packages:import-package {composerName : The composer name of the package to import (e.g., vendor/package)}';

    protected $description = 'Import a package by its composer name and process its versions.';

    public function handle(PackageRepositoryContract $packageRepository, PackagistServiceContract $packageService): void
    {
        $package = Package::query()->with('versions')->firstWhere('name', $this->argument('composerName'));

        if (empty($package)) {
            $matches = $packageService->search($this->argument('composerName', ''));

            $packageRepository->syncPackages($matches, true);
        }

        $package = Package::query()->with('versions')->firstWhere('name', $this->argument('composerName'));

        if (empty($package)) {
            $this->error("Package {$this->argument('composerName')} not found after sync.");
            return;
        }

        if ($package->versions()->count() === 0) {
            $this->error("Package {$package->name} has no versions.");
            return;
        }

        foreach ($package->versions as $version) {
            $this->info("Processing version {$version->version} of package {$package->name}");
            // Dispatch job to process the version
            dispatch(new RepackageVersionInZipJob($package, $version));
        }
    }
}
