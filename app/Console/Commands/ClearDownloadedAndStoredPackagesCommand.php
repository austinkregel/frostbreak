<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;

class ClearDownloadedAndStoredPackagesCommand extends Command
{
    protected $signature = 'packages:clear';

    protected $description = 'Clears the downloaded cache of stored packages and their versions.';

    public function handle(FilesystemManager $manager)
    {
        $filesystem = $manager->disk('app');

        if (!$filesystem->deleteDirectory('archive')) {
            $this->error('Failed to clear the archive directory. It may not exist.');
            return;
        }

        if (file_exists(base_path('archive'))) {
            $this->error('Failed to delete the archive directory. It may not be empty or there may be permission issues.');
            dd();
        }

        if (!$filesystem->makeDirectory('archive')) {
            $this->error('Failed to recreate the archive directory.');
            return;
        }
        $this->info('Cleared the archive directory.');

        if (!$filesystem->delete($filesystem->files('storage/downloads'))) {
            $this->error('Failed to clear the downloads directory. It may not exist.');
            return;
        }
        if (!$filesystem->makeDirectory(storage_path('downloads'))) {
            $this->error('Failed to recreate the downloads directory.');
            return;
        }
        $this->info('Cleared the downloads directory.');

        if (!$filesystem->deleteDirectory('storage/packages')) {
            $this->error('Failed to clear the packages directory. It may not exist.');
            return;
        }
        if (!$filesystem->makeDirectory('storage/packages')) {
            $this->error('Failed to recreate the packages directory.');
            return;
        }
        $this->info('Cleared the packages directory.');

        Version::truncate()->get();
        $this->info('Cleared all versions from the database.');
        Package::truncate()->get();
        $this->info('Cleared all packages from the database.');
        $this->info('All downloaded and stored packages have been cleared successfully.');

    }
}
