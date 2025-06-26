<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Contracts\Services\PackagistServiceContract;
use Illuminate\Console\Command;

class BuildThePackageIndexFromPackagistCommand extends Command
{

    protected $name = 'packages:build-index-from-packagist';
    protected $description = 'Builds the package (both plugin and themes) and fetching metadata from Packagist.';

    public function handle(PackageRepositoryContract $packageRepository, PackagistServiceContract $packageService): void
    {
        $this->validateGithubCredentials();
        $query = $this->ask('Enter search query for Packagist (default: wintercms)') ?: 'wintercms';

        $matches = $packageService->search($query);

        $packageRepository->syncPackages($matches);
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


