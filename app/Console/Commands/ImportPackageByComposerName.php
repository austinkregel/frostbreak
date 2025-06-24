<?php

namespace App\Console\Commands;

use App\Jobs\RepackageVersionInZipJob;
use App\Models\Package;
use App\Repositories\PackageRepository;
use App\Services\PackagistClient;
use App\Services\PackagistService;
use Illuminate\Console\Command;

class ImportPackageByComposerName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:import-package {composerName : The composer name of the package to import (e.g., vendor/package)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $package = Package::query()->with('versions')->firstWhere('name', $this->argument('composerName'));

        if (empty($package)) {
            $matches = (new PackagistService(new PackagistClient()))->search($this->argument('composerName', ''));

            app(PackageRepository::class)->syncPackages($matches, true);
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
            dispatch_sync(new RepackageVersionInZipJob($package, $version));
        }

//       dispatch_sync(new )
    }
}
