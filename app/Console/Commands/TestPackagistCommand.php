<?php

namespace App\Console\Commands;

use App\Jobs\RepackageVersionInZipJob;
use App\Repositories\PackageRepository;
use App\Services\PackagistService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Package;
use App\Services\PackagistClient;
use Illuminate\Filesystem\Filesystem;

class TestPackagistCommand extends Command
{

    protected $name = 'kregel:test-packagist';
    protected $description = 'Test searching and fetching metadata from Packagist.';

    public function handle()
    {
        $this->validateGithubCredentials();
        $query = $this->ask('Enter search query for Packagist (default: wintercms)') ?: 'wintercms';

        $matches = (new PackagistService(new PackagistClient()))->search($query);

        $filesystem = new Filesystem();

        (new PackageRepository($filesystem))->syncPackages($matches);
    }
}


