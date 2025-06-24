<?php

namespace App\Jobs;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class RepackageVersionInZipJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Package $package,
        protected Version $version,
        protected bool    $force = false,
    ) {
    }

    public function handle(FilesystemManager $manager): void
    {
        $packageFilesystem = $manager->disk('packages');
        $downloadFilesystem = $manager->disk('downloads');
        $archiveFilesystem = $manager->disk('archive');
        $archiveFolderPath  = $archiveFilesystem->path('');
        $downloadCacheDirectory  = $downloadFilesystem->path('');

        $package = $this->package;
        $version = $this->version;

        if (empty($version)) {
            $this->error("  [!] No latest version found for package: {$package->name}");
            return;
        }

        $locationOfProcessedVersion = $packageFilesystem->path($version->getCacheLocation());

        // If the author code and current version exists as a zip we're skipping this package
        if ($packageFilesystem->exists($locationOfProcessedVersion)) {
            $this->warn("    [!] Skipping package already processed: {$package->name} - $locationOfProcessedVersion");

            // We need to create md5 sum for the zip file, and save it to the version
            $md5Hash = md5($packageFilesystem->get($locationOfProcessedVersion));

            if ($version->hash === $md5Hash) {
                $this->info("  [!] Package already processed with same hash: {$package->name}; no updated needed.");
                return;
            }
        }

        $cloneProcess = new Process([
            'wget', $version->dist_url, '-O', $archive = $downloadCacheDirectory . Uuid::uuid4() . '.zip',
        ]);
        $cloneProcess->run();

        if (!$cloneProcess->isSuccessful()) {
            $this->info($cloneProcess->getOutput());
            $this->error($cloneProcess->getErrorOutput());
            $this->error("  [!] Failed to download package archive for: {$package->name}");
            return;
        }

        // From here on, we need to use $archiveFilesystem unzip we're ready to repackage
        $unzipProcess = new Process([
            'unzip', '-o', $archive,
        ], $archiveFolderPath);
        $unzipProcess->run();

        if (!$unzipProcess->isSuccessful()) {
            $this->info($unzipProcess->getOutput());
            $this->error($unzipProcess->getErrorOutput());
            $this->error("  [!] Failed to clone repository for package: {$package->name}");
            return;
        }

        $output = explode("\n", $unzipProcess->getOutput());

        if (count($output) > 3) {
            $createdDirectoryLogs = explode(" ", trim($output[2]));
            $createdDirectoryLog = Arr::last($createdDirectoryLogs);
        } else {
            dd(
                "This shouldn't happen, but just in case; We failed to get the contents of the zip file there were less than 3 lines of output.",
                $unzipProcess->getOutput(),
                $unzipProcess->getErrorOutput(),

                $output,
            );
        }

        $this->info("  [!] Cloned package: {$package->name} to {$archiveFolderPath}{$createdDirectoryLog}");

        $allDirs = array_filter(
            $dirs = $archiveFilesystem->directories('/'),
            fn ($dir) => basename($dir) === basename($createdDirectoryLog)
        );

        if (empty($allDirs)) {
            $this->error("  [!] No directories found in package directory for: {$package->name}");
            dd($dirs, $allDirs);
            return;
        }

        if (count($allDirs) > 1) {
            $this->error("  [!] Multiple directories found in package directory for: {$package->name}");
            $this->error("  [!] Directories: " . implode(', ', $allDirs));
            return;
        }
        $directoryWhereTheCodeLives = Arr::first($allDirs);

        // --- Directory renaming and zipping logic ---
        // Convert package code (e.g., Winter.Users) to directory path (winter/users)
        $originalCode = '';
        // Try to extract from Plugin.php if not set
        if ($archiveFilesystem->exists($directoryWhereTheCodeLives . '/Plugin.php')) {
            $content = $archiveFilesystem->get($directoryWhereTheCodeLives . '/Plugin.php');
            preg_match('/namespace\s+([^;]+);/', $content, $matches);
            if (isset($matches[1])) {
                $package->code = $originalCode = str_replace('\\', '.', $matches[1]);
            } else {
                dd($originalCode, $package->code, $package->name, $matches, $content, $directoryWhereTheCodeLives);
            }
        }
        $packageCodeAsFolder = strtolower(str_replace('.', '/', $originalCode));

        $keywordsToAdd = $package->keywords;
        if ($archiveFilesystem->exists($directoryWhereTheCodeLives . '/Plugin.php')) {
            $keywordsToAdd[] = 'plugin';
            $this->info('Found Plugin.php, marking as plugin.');
        }

        if ($archiveFilesystem->exists($directoryWhereTheCodeLives . '/theme.yaml')) {
            $this->info('Found theme.yaml, marking as theme.');
            $keywordsToAdd[] = 'theme';
            $themeYamlContent = yaml_parse($archiveFilesystem->get($directoryWhereTheCodeLives . '/theme.yaml'));

            if (isset($themeYamlContent['code'])) {
                $packageCodeAsFolder = $themeYamlContent['code'];
                $package->code = $themeYamlContent['code'];
                $this->info("  [!] Theme detected, using name from theme.yaml: {$packageCodeAsFolder}");
            } else if (isset($themeYamlContent['name'])) {
                $packageCodeAsFolder = strtolower(str_replace(' ', '-', $themeYamlContent['name']));
                $this->info("  [!] Theme detected, using name from theme.yaml: {$packageCodeAsFolder}");
                $package->name = $themeYamlContent['name'];
            }
        }
        $package->keywords = array_values(array_unique($keywordsToAdd));

        if (empty($packageCodeAsFolder)) {
            $packageCodeAsFolder = $package->name;
            $package->code = $package->name;
        }
        if ($package->isDirty(['keywords', 'code', 'name',])) {
            $package->save();
        }


        if (empty($packageCodeAsFolder)) {
            $this->error("  [!] Could not determine package code for: {$package->name}; This is unsupported.");
            $package->update(['is_approved' => false]);
            $this->cleanup(
                $archiveFilesystem,
                $directoryWhereTheCodeLives,
                $archive,
                $packageFilesystem,
                $newFolderForCode ?? 'not defined',
            );
            return;
        }

        // TODO: Possibly implement a hook or event to allow our system to do async operations

        if (in_array('plugin', $package->keywords)) {
            // If the package code is Winter.*, and the name is not winter/* we need to delete it.
            // These packages will not be supported by this application.
            if (Str::startsWith($packageCodeAsFolder, 'Winter.') && !Str::startsWith($package->name, 'winter/')) {
                $this->error("  [!] Package code {$packageCodeAsFolder} does not match package name {$package->name}. Skipping.");
                $package->versions->map(function ($version) {
                    $version->delete();
                });

                $package->delete();
                $this->cleanup(
                    $archiveFilesystem,
                    $directoryWhereTheCodeLives,
                    $archive,
                    $packageFilesystem,
                    $newFolderForCode ?? 'not defined',
                );
                return;
            }
        }

        $authorCode = dirname($packageCodeAsFolder);

        if (in_array('theme', $package->keywords)) {
            // If it's really a theme we need to look at the theme.yaml file to get the name and lower case it to replace the packagecode.
            $themeYamlPath = $directoryWhereTheCodeLives . '/theme.yaml';
            if ($packageFilesystem->exists($themeYamlPath)) {

                $themeYamlContent = yaml_parse($packageFilesystem->get($themeYamlPath));
                if (isset($themeYamlContent['name'])) {
                    $packageCodeAsFolder = strtolower(str_replace(' ', '-', $themeYamlContent['name']));
                    $this->info("  [!] Theme detected, using name from theme.yaml: {$packageCodeAsFolder}");
                }
            }
        }

        if (empty($packageCodeAsFolder)) {
            $packageCodeAsFolder = $package->name;
            $authorCode = dirname($packageCodeAsFolder);
        }

        $packageFilesystem->makeDirectory($authorCode, 0775, true, true);
        // Rename extracted dir to targetn
        Storage::disk('app')->move(
            'storage/archive/'.$directoryWhereTheCodeLives,
            $newFolderForCode = 'storage/archive/'.$packageCodeAsFolder
        );

        $this->info('  [!] Moved the source code to disk(app) -> ' . $newFolderForCode);

        $version->refresh();

        $packageFilesystem->makeDirectory($package->name);
        $locationOfProcessedVersion = $packageFilesystem->path($relativeZipFileName = $package->name.'/'.Str::slug($version->semantic_version).'.zip');

        $zipProcess = new Process([
            'zip', '-rm', $locationOfProcessedVersion, $packageCodeAsFolder
        ], $archiveFilesystem->path(''));
        $zipProcess->run();
        $this->info('  [!] Zipped contents to: ' . $locationOfProcessedVersion);

        if (!$packageFilesystem->exists($relativeZipFileName)) {
            $this->error("  [!] Failed creating zip file: {$locationOfProcessedVersion}");
            $this->info("  [!] Process output: " . $zipProcess->getOutput());
            $this->error("  [!] Process error output: " . $zipProcess->getErrorOutput());
            $this->cleanup(
                $archiveFilesystem,
                $directoryWhereTheCodeLives,
                $archive,
                $packageFilesystem,
                $newFolderForCode
            );
            return;
        }
        $this->info("  [+] Zip file successfully created: {$locationOfProcessedVersion}");

        // We need to fetch the md5 sum for the zip file, and save it to the version
        $md5Hash = md5($packageFilesystem->get($relativeZipFileName));

        $version->hash = $md5Hash;
        $version->save();

        if (!$zipProcess->isSuccessful()) {
            $this->info($zipProcess->getOutput());
            $this->error($zipProcess->getErrorOutput());
            $this->error("  [!] Failed to zip package for: {$package->name}");
            $this->cleanup(
                $archiveFilesystem,
                $directoryWhereTheCodeLives,
                $archive,
                $packageFilesystem,
                $newFolderForCode
            );
            return;
        }
        $this->info("  [!] Zipped package: {$package->name} to {$locationOfProcessedVersion}");
        // Cleanup; we don't want to keep the extracted code in the cache directory

        $this->cleanup(
            $archiveFilesystem,
            $directoryWhereTheCodeLives,
            $archive,
            $packageFilesystem,
            $newFolderForCode
        );

        $package->needs_additional_processing = false;
        $package->save();

        $this->warn("[+] Processed package: {$package->name}");

        $this->info("  [i] Package available at: " . $locationOfProcessedVersion);
        $this->info("  [i] Package directoryWhereTheCodeLives at: " . $directoryWhereTheCodeLives);
        $this->info("  [i] Package newFolderForCode at: " . $newFolderForCode);
        $this->info("  [i] Package archive at: " . $archive);
    }

    protected function warn(string $message): void
    {
        app(LoggerInterface::class)->info($message);
    }
    protected function info(string $message): void
    {
        app(LoggerInterface::class)->debug($message);
    }
    protected function error(string $message): void
    {
        app(LoggerInterface::class)->error($message);
    }

    private function cleanup(
        $archiveFilesystem,
        $directoryWhereTheCodeLives,
        $archive,
        $packageFilesystem,
        $newFolderForCode,
    ): void {
        if (
            $archiveFilesystem->exists($directoryWhereTheCodeLives)
        ) {
            @$archiveFilesystem->deleteDirectory($directoryWhereTheCodeLives);
            $this->warn('      [-] Deleted parent directory: ' . $directoryWhereTheCodeLives);
        }

        if (file_exists($archive)) {
            @unlink($archive);
            $this->warn('      [-] Deleted archive we downloaded: ' . $archive);
        }

        if (Storage::disk('app')->exists($newFolderForCode)) {
            @Storage::disk('app')->deleteDirectory($newFolderForCode);
            $this->warn('      [-] Deleted new folder for code: ' . $newFolderForCode);
        }
    }
}
