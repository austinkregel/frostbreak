<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Jobs\RepackageVersionInZipJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ExtraProcessingForPackagesCommand extends Command
{
    protected $signature = 'packages:extra-processing';

    protected $description = 'Perform extra processing for packages';

    public function handle(PackageRepositoryContract $packageRepository)
    {
        $page = 1;
        do {
            $packages = $packageRepository->searchByCode('later', 15, $page++);
            $this->info('');
            foreach ($packages as $package) {
                $jobs = [];
                $this->info("[!] Processing package: {$package->name}");
                $this->info("[!] {$package->versions->count()}");
                foreach ($package->versions as $latestVersion) {
                    $jobs[] = new RepackageVersionInZipJob($package, $latestVersion);
                }

                Bus::chain($jobs)->dispatch();
            }
        } while ($packages->hasMorePages());
    }
}
