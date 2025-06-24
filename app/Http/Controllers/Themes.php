<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Themes extends Controller
{
    public function details(Request $request)
    {
        $packageNames = $request->get('names', []);
        $packages = Package::query()
            ->whereIn('name', $packageNames)
            ->where('needs_additional_processing', false)
            ->whereJsonContains('keywords', 'theme')
            ->get();
        return response()->json($packages);
    }

    public function detail(Request $request)
    {
        $packageName = $request->get('name');

        $package = Package::query()
            ->where('code', $packageName)
            ->where('needs_additional_processing', false)
            ->whereJsonContains('keywords', 'theme')
            ->first();

        if (!$package) {
            return response()->json([
                'error' => 'Theme not found',
            ], 404);
        }
        return response()->json($package);
    }

    public function search(Request $request)
    {
        $packageName = $request->get('query');
        // If using Laravel Scout, otherwise fallback to a simple where
        $packages = Package::where('code', 'like', "%{$packageName}%")
            ->whereJsonContains('keywords', 'theme')
            ->whereJsonDoesntContain('keywords', 'october')
            ->limit(10)
            ->get();

        return response()->json($packages);
    }

    public function popular(Request $request)
    {
        $packages = Package::orderByDesc('downloads')
            ->whereJsonContains('keywords', 'theme')
            ->where('needs_additional_processing', false)
            ->orderByDesc('favers')
            ->limit(10)
            ->get();
        return response()->json($packages);
    }
}
