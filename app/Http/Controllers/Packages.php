<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Filesystem\Filesystem;

class Packages extends Controller
{
    public function __construct(protected Filesystem $files)
    {
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
            ->firstOrFail();
        return response()->json($package);
    }

    public function search(Request $request)
    {
        $packageName = $request->get('query');
        $packages = Package::where('name', 'like', "%{$packageName}%")
            ->whereJsonContains('keywords', 'plugin')
            ->whereJsonDoesntContain('keywords', 'october')
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
            ->firstOrFail();

        $latestVersion = $package->versions()->orderByDesc('released_at')->first();

        abort_if(empty($latestVersion->dist_url), 404, 'No distribution URL found for this package.');
        $contentType = 'application/octet-stream';
        $contentDisposition = 'attachment; filename="' . $package->name . '-' . $latestVersion->semantic_version . '.zip"';

        $responseDownload = $this->files->get($latestVersion->getCacheLocation());

        if ($latestVersion->hash !== md5($responseDownload)) {
            abort(500, 'The downloaded package does not match the expected hash.');
        }

        return response($responseDownload, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }
}
