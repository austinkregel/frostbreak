<?php

namespace App\Console\Commands;

use App\Jobs\RepackageVersionInZipJob;
use Illuminate\Console\Command;

class ExtraProcessingForPackagesCommand extends Command
{
    protected $signature = 'kregel:packages-extra-processing';

    protected $description = 'Perform extra processing for packages';

    public function handle()
    {
        $page = 1;
        do {
            $packages = \App\Models\Package::query()
                ->where('code', 'like', '%/%')
                ->paginate(perPage: 100, page: $page++);
            $this->info('');
            foreach ($packages as $package) {
                $this->info("[!] Processing package: {$package->name}");
                foreach ($package->versions as $latestVersion) {
                    dispatch_sync(new RepackageVersionInZipJob($package, $latestVersion));
                }
            }
        } while ($packages->hasMorePages());
    }
}
