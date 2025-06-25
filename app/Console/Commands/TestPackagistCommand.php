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

        app(PackageRepository::class)->syncPackages($matches);
    }


    protected function validateGithubCredentials(): void
    {
        if (empty(env('GITHUB_USERNAME')) || empty(env('GITHUB_TOKEN'))) {
            $this->error('Please set GITHUB_USERNAME and GITHUB_TOKEN in your .env file.');
            throw new \DomainException('Defined the GITHUB_USERNAME and GITHUB_TOKEN environment variables in your .env file.');
            exit(1);
        }
    }
}


