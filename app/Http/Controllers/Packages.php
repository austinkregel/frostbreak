<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Packages extends Controller
{
    public function __construct(protected FilesystemManager $manager, protected mixed $filesystem = null)
    {
        $this->filesystem = $manager->disk('packages');
    }

    public function details(Request $request)
    {
        $packageNames = $request->get('names', []);
        $packages = Package::query()
            ->whereJsonContains('keywords', 'plugin')
            ->whereJsonDoesntContain('keywords', 'october')
            ->whereIn('code', $packageNames)
            ->where('needs_additional_processing', false)
            ->get();
        return response()->json($packages);
    }

    public function detail(Request $request)
    {
        $packageName = $request->get('name');
        $package = Package::query()
            ->whereJsonContains('keywords', 'plugin')
            ->whereJsonDoesntContain('keywords', 'october')
            ->where('code', $packageName)
            ->first();

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
        $packages = Package::where('code', 'like', "%{$packageName}%")
            ->whereJsonContains('keywords', 'plugin')
            ->limit(10)
            ->get();

        return response()->json($packages);
    }

    public function popular(Request $request)
    {
        $packages = Package::orderByDesc('downloads')
            ->where('needs_additional_processing', false)
            ->whereJsonDoesntContain('keywords', 'october')
            ->whereJsonContains('keywords', 'plugin')
            ->orderByDesc('favers')
            ->limit(10)
            ->get();
        return response()->json($packages);
    }

    public function package(Request $request)
    {
        $packageName = $request->get('name');
        $package = Package::query()
            ->where('needs_additional_processing', false)
            ->where('code', $packageName)
            ->first();

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

        $packageDestination = str_replace('.', '/', $package->name);

        $location = $packageDestination . '/' . $version . '.zip';


        if (!Storage::disk('packages')->exists($location)) {
            return response()->json([
                'error' => 'Package version not found',
                'exists' => Storage::disk('packages')->exists($location),
                'path' => $location,
            ], 410);
        }

        $contentType = 'application/octet-stream';
        $contentDisposition = 'attachment; filename="' . $package->name . '-' . $version . '.zip"';

        $responseDownload = Storage::disk('packages')->get($location);

        if ($latestVersion->hash !== md5($responseDownload)) {
            abort(500, 'The downloaded package does not match the expected hash.');
        }

        return response($responseDownload, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }
}
