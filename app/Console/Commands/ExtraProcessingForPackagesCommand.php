<?php

namespace App\Console\Commands;

use App\Jobs\RepackageVersionInZipJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ExtraProcessingForPackagesCommand extends Command
{
    protected $signature = 'kregel:packages-extra-processing';

    protected $description = 'Perform extra processing for packages';

    public function handle()
    {
        $jobs = [];
        $page = 1;
        do {
            $packages = \App\Models\Package::query()
                ->where('code', 'later')
                ->paginate(perPage: 100, page: $page++);
            $this->info('');
            foreach ($packages as $package) {
                $this->info("[!] Processing package: {$package->name}");
                $this->info("[!] {$package->versions->count()}");
                foreach ($package->versions as $latestVersion) {
                    dispatch_sync(new RepackageVersionInZipJob($package, $latestVersion));
                }
            }
        } while ($packages->hasMorePages());
    }
}
