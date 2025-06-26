<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Models\Package;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Packages extends Controller
{
    public function __construct(
        protected FilesystemManager $manager,
        protected PackageRepositoryContract $packageRepository,
        protected mixed $filesystem = null
    ) {
        $this->filesystem = $manager->disk('packages');
    }

    public function details(Request $request)
    {
        $packageNames = $request->get('names', []);

        return response()->json($this->packageRepository->findAllPackageDetails($packageNames));
    }

    public function detail(Request $request)
    {
        $packageName = $request->get('name');
        $package = $this->packageRepository->findPluginByCode($packageName);

        if (!$package) {
            return response()->json([
                'error' => 'Package not found',
            ], 404);
        }

        return response()->json($package);
    }

    public function search(Request $request)
    {
        $packageName = $request->get('query');
        $packages = $this->packageRepository->searchByCode($packageName, 10);
        return response()->json($packages->items());
    }

    public function popular(Request $request)
    {;
        return response()->json($this->packageRepository->findSomePopular(10)->items());
    }

    public function package(Request $request)
    {
        $packageName = $request->get('name');
        $package = $this->packageRepository->findByCode($packageName);

        if (!$package) {
            return response()->json([
                'error' => 'Package not found',
            ], 404);
        }

        $latestVersion = $package->versions()
            ->where('semantic_version', 'not like', '%-dev')
            ->where('semantic_version', 'not like', 'dev-%')
            ->orderByDesc('released_at')
            ->first();

        if (empty($latestVersion)) {
            $latestVersion = $package->versions()
                ->orderByDesc('released_at')
                ->first();
        }

        if (empty($latestVersion)) {
            return response()->json([
                'error' => 'No stable versions available for this package',
            ], 404);
        }

        $version = Str::slug($latestVersion->semantic_version ?? 'latest');
        // Use getCacheLocation() for the file path
        $location = $latestVersion->getCacheLocation();

        if (!$this->filesystem->exists($location)) {
            return response()->json([
                'error' => 'Package version not found',
                'exists' => $this->filesystem->exists($location),
                'path' => $location,
            ], 410);
        }

        $contentType = 'application/octet-stream';
        $contentDisposition = 'attachment; filename="' . $package->name . '-' . $version . '.zip"';

        $responseDownload = $this->filesystem->get($location);

        if ($latestVersion->hash !== md5($responseDownload)) {
            abort(500, 'The downloaded package does not match the expected hash.');
        }

        return response($responseDownload, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }
}
