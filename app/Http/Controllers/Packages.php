<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Packages extends Controller
{
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
            ->whereJsonContains('keywords', 'plugin')
            ->whereJsonDoesntContain('keywords', 'october')
            ->where('needs_additional_processing', false)
            ->where('code', $packageName)
            ->firstOrFail();
        $latestVersion = $package->versions()->orderByDesc('released_at')->first();

        abort_if(empty($latestVersion->dist_url), 404, 'No distribution URL found for this package.');
        $response = Http::head($latestVersion->dist_url);
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to fetch video headers'], 500);
        }
        // Get content type and content length
        $contentType = $response->header('Content-Type', 'application/octet-stream');
        $contentDisposition = $response->header('content-disposition');

        $responseDownload = Http::get($latestVersion->dist_url);

        return response($responseDownload, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }
}
