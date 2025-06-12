<?php

namespace App\Jobs;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class RepackageVersionInZipJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Package $package,
        protected Version $latestVersion,

        protected bool $force = false,
    ) {
    }

    public function handle(): void
    {
        $packageCacheDirectory  = base_path('archive');
        $downloadCacheDirectory  = storage_path('downloads');
        $filesystem = new Filesystem();
        $filesystem->makeDirectory($packageCacheDirectory, 0755, true, true);
        $filesystem->makeDirectory($downloadCacheDirectory, 0755, true, true);

        $package = $this->package;
        $latestVersion = $this->latestVersion;

        $version = Str::slug($latestVersion->semantic_version ?? 'latest');
        if (empty($latestVersion)) {
            $this->error("  [!] No latest version found for package: {$package->name}");
            return;
        }

        $filesystem->makeDirectory($packageDestination = storage_path('packages/' . $package->name), 0755, true, true);

        $locationOfProcessedVersion = $latestVersion->getCacheLocation();

        // If the author code and current version exists as a zip we're skipping this package
        if (file_exists($locationOfProcessedVersion)) {
            $this->warn("    [!] Skipping package already processed: {$package->name} - $locationOfProcessedVersion");

            // We need to create md5 sum for the zip file, and save it to the version
            $md5Hash = md5_file($locationOfProcessedVersion);

            if ($latestVersion->hash === $md5Hash) {
                $this->info("  [!] Package already processed with same hash: {$package->name}");
                return;
            }

            $latestVersion->hash = $md5Hash;
            $latestVersion->save();


            return;
        }

        $cloneProcess = new Process([
            'wget', $latestVersion->dist_url, '-O', $archive = $downloadCacheDirectory . '/' . Uuid::uuid4() . '.zip',
        ]);
        $cloneProcess->run();

        if (!$cloneProcess->isSuccessful()) {
            $this->info($cloneProcess->getOutput());
            $this->error($cloneProcess->getErrorOutput());
            $this->error("  [!] Failed to download package archive for: {$package->name}");
            return;
        }

        $unzipProcess = new Process([
            'unzip', '-o', $archive,
        ], $packageCacheDirectory);
        $unzipProcess->run();

        if (!$unzipProcess->isSuccessful()) {
            $this->info($unzipProcess->getOutput());
            $this->error($unzipProcess->getErrorOutput());
            $this->error("  [!] Failed to clone repository for package: {$package->name}");
            return;
        }

        $output = explode("\n", $unzipProcess->getOutput());

        if (count($output) > 3) {
            $createdDirectoryLog = explode(" ", trim($output[2]));
            $createdDirectoryLog = Arr::last($createdDirectoryLog);
        }


        $this->info("  [!] Cloned package: {$package->name} to {$packageCacheDirectory}/{$createdDirectoryLog}");

        $allDirs = array_filter($dirs = $filesystem->directories($packageCacheDirectory), fn ($dir) => basename($dir) === basename($createdDirectoryLog));
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
        $originalCode = $package->code ?? null;
        if (!$originalCode) {
            // Try to extract from Plugin.php if not set
            if (file_exists($directoryWhereTheCodeLives . '/Plugin.php')) {
                $content = file_get_contents($directoryWhereTheCodeLives . '/Plugin.php');
                preg_match('/namespace\s+([^;]+);/', $content, $matches);
                if (isset($matches[1])) {
                    $originalCode = str_replace('\\', '.', $matches[1]);
                }
            }
        }

        $keywordsToAdd = $package->keywords;
        if (file_exists($directoryWhereTheCodeLives . '/Plugin.php')) {
            $keywordsToAdd[] = 'plugin';
        }

        if (file_exists($directoryWhereTheCodeLives . '/theme.yaml')) {
            $keywordsToAdd[] = 'theme';
        }
        $package->keywords = array_values(array_unique($keywordsToAdd));

        if ($package->isDirty('keywords')) {
            $package->save();
        }

        $packageCode = strtolower(str_replace('.', '/', $originalCode));

        if (!$packageCode) {
            $this->error("  [!] Could not determine package code for: {$package->name}");
            return;
        }

        // TODO: Possibly implement a hook or event to allow our system to do async operations

        if (in_array('plugin', $package->keywords)) {
            // If the package code is Winter.*, and the name is not winter/* we need to delete it.
            // These packages will not be supported by this application.
            if (Str::startsWith($packageCode, 'Winter.') && !Str::startsWith($package->name, 'winter/')) {
                $this->error("  [!] Package code {$packageCode} does not match package name {$package->name}. Skipping.");
                $package->versions->map(function ($version) {
                    $version->delete();
                });

                $package->delete();
                return;
            }
        }

        $authorCode = dirname($packageCode);

        if (in_array('theme', $package->keywords)) {
            // If it's really a theme we need to look at the theme.yaml file to get the name and lower case it to replace the packagecode.
            $themeYamlPath = $directoryWhereTheCodeLives . '/theme.yaml';
            if (file_exists($themeYamlPath)) {
                $themeYamlContent = yaml_parse_file($themeYamlPath);
                if (isset($themeYamlContent['name'])) {
                    $packageCode = strtolower(str_replace(' ', '-', $themeYamlContent['name']));
                    $this->info("  [!] Theme detected, using name from theme.yaml: {$packageCode}");
                }
            }
        }

        $filesystem->makeDirectory($newFolderForCode = $packageCacheDirectory . '/' . $packageCode, 0755, true, true);
        // Rename extracted dir to target
        $filesystem->move($directoryWhereTheCodeLives, $newFolderForCode);

        $this->info('  [!] Renamed directory inside zip to: ' . $newFolderForCode);

        $zipProcess = new Process([
            'zip', '-rm', $locationOfProcessedVersion, $packageCode
        ], $packageCacheDirectory);
        $zipProcess->run();
        $this->info('  [!] Zipped contents to: ' . $locationOfProcessedVersion);

        if (!$filesystem->exists($locationOfProcessedVersion)) {
            $this->error("  [!] Failed creating zip file: {$locationOfProcessedVersion}");
            $this->info("  [!] Process output: " . $zipProcess->getOutput());
            $this->error("  [!] Process error output: " . $zipProcess->getErrorOutput());
            return;
        }
        $this->info("  [!] Zip file exists: {$locationOfProcessedVersion}");

        // We need to create md5 sum for the zip file, and save it to the version
        $md5Hash = md5_file($locationOfProcessedVersion);
        $latestVersion->hash = $md5Hash;
        $latestVersion->save();

        if (!$zipProcess->isSuccessful()) {
            $this->info($zipProcess->getOutput());
            $this->error($zipProcess->getErrorOutput());
            $this->error("  [!] Failed to zip package for: {$package->name}");
            return;
        }
        $this->info("  [!] Zipped package: {$package->name} to {$locationOfProcessedVersion}");

        $authorCodeLocation = $packageCacheDirectory . '/' . $authorCode;

        // Cleanup; we don't want to keep the extracted code in the cache directory
        if (file_exists($authorCodeLocation)) {
            $filesystem->deleteDirectory($newFolderForCode);
            $this->warn('      [-] Deleted new folder for code: ' . $newFolderForCode);
            $filesystem->deleteDirectory($packageCacheDirectory . '/' . $authorCode);
            $this->warn('      [-] Deleted parent directory: ' . $packageCacheDirectory . '/' . $authorCode);
            $filesystem->delete($archive);
            $this->warn('      [-] Deleted archive we downloaded: ' . $archive);
        }

        $package->code = $originalCode;
        $package->needs_additional_processing = false;
        $package->save();

        $this->warn("    [+] Processed package: {$package->name}");
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
}
