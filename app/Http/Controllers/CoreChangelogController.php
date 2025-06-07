<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoreChangelogController extends Controller
{
    public function changelog(Request $request, $branch = 'main')
    {
        $package = \App\Models\Package::where('code', 'Winter.Storm')->first();
        // Return the content as a JSON response
        return response()->json([
            'branch' => $branch,
            'history' => $package->versions()
                ->where('semantic_version', 'like', $branch.'%')
                ->orderBy('released_at', 'desc')
                ->get()
                ->map(function ($version) use ($branch) {
                    return [
                        'id' => $version->id,
                        'build' => $version->semantic_version,
                        'description' => $version->description,
                        'description_html' => $version->description,

                        'link_url' => $version->link_url,
                        'version_full' => $version->semantic_version,
                        'version' => $branch,

                        'created_at' => $version->released_at,
                        'updated_at' => $version->updated_at,
                    ];
                }),
        ]);
    }
}
