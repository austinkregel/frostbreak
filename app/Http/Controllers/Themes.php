<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

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
            ->where('name', $packageName)
            ->where('needs_additional_processing', false)
            ->whereJsonContains('keywords', 'theme')
            ->firstOrFail();
        return response()->json($package);
    }

    public function search(Request $request)
    {
        $packageName = $request->get('query');
        // If using Laravel Scout, otherwise fallback to a simple where
        if (method_exists(Package::class, 'search')) {
            $packages = Package::search($packageName)
                ->whereJsonContains('keywords', 'theme')
                ->where('needs_additional_processing', false)
                ->limit(10)
                ->get();
        } else {
            $packages = Package::where('name', 'like', "%{$packageName}%")
                ->where('needs_additional_processing', false)
                ->whereJsonContains('keywords', 'theme')
                ->limit(10)
                ->get();
        }
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
