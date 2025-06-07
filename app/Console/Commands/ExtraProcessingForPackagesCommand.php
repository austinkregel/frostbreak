<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;

class ExtraProcessingForPackagesCommand extends Command
{
    protected $signature = 'kregel:packages-extra-processing';

    protected $description = 'Perform extra processing for packages';

    public function handle()
    {
        $page = 1;
        $filesystem = new Filesystem();
        do {
            $packages = \App\Models\Package::query()
                ->where('needs_additional_processing', true)
                ->whereNotIn('name', [
                    'winter/wn-backend-module',
                    'winter/wn-cms-module',
                ])
                ->paginate(page: $page, perPage: 100);

            foreach ($packages as $package) {
                $this->info("Processing package: {$package->name}");

                $composerName = $package->name;

                $latestVersion = $package->latestVersion;
                if (empty($latestVersion)) {
                    $this->error("No latest version found for package: {$package->name}");
                    continue;
                }

                $filesystem->makeDirectory($directory = storage_path('packages/'.$composerName), 0755, true, true);

                if (!file_exists($directory.'/composer.json')) {
                    $cloneProcess = new Process([
                        'wget', $latestVersion->dist_url, '-O', $archive = storage_path('packages/'.$composerName.'/archive.zip'),
                    ]);
                    $cloneProcess->run();

                    if (!$cloneProcess->isSuccessful()) {
                        $this->info($cloneProcess->getOutput());
                        $this->error($cloneProcess->getErrorOutput());
                        $this->error("Failed to download package archive for: {$package->name}");
                        continue;
                    }

                    $unzipProcess = new Process([
                        'unzip', '-o', storage_path('packages/'.$composerName.'/archive.zip'),
                    ], $directory);
                    $unzipProcess->run();

                    if (!$unzipProcess->isSuccessful()) {
                        $this->info($unzipProcess->getOutput());
                        $this->error($unzipProcess->getErrorOutput());
                        $this->error("Failed to clone repository for package: {$package->name}");
                        continue;
                    }

                    $this->info("Cloned package: {$package->name} to {$directory}");
                }
                $allDirs = $filesystem->directories($directory);
                if (count($allDirs) > 1) {
                    $this->error("Multiple directories found in package directory for: {$package->name}");
                    $this->error("Directories: " . implode(', ', $allDirs));
                    continue;
                }
                $originalDirectory = $directory;
                $directory = Arr::first($allDirs);
                if (file_exists($pluginPath = $directory.'/Plugin.php')) {
                    $this->info("Plugin.php found for package: {$package->name}");
                    $content = file_get_contents($pluginPath);
                    // We need to extract the plugin Namespace;
                    preg_match('/namespace\s+([^;]+);/', $content, $matches);

                    if (isset($matches[1])) {
                        $pluginNamespace = str_replace('\\', '.', $matches[1]);
                        $this->info("Plugin namespace for package {$package->name}: {$pluginNamespace}");
                        $package->needs_additional_processing = false;
                        $package->code = $pluginNamespace;
                        $package->save();
                    } else {
                        $this->error("Failed to extract plugin namespace for package: {$package->name}");
                    }
                }

                if (file_exists($composerPath = $directory.'/composer.json')) {
                    $filesystem->deleteDirectory($directory);
                    $filesystem->deleteDirectory($originalDirectory);
                    $filesystem->delete($archive);
                }

                // After processing, you might want to update the package
                $package->needs_additional_processing = false;
                $package->save();

                $this->info("Processed package: {$package->name}");
            }

        } while ($packages->hasMorePages());
    }
}
