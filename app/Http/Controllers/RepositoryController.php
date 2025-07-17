<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Repository;
use App\Models\Package;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class RepositoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Only load packages where the repository is owned by or accessible to the user
        $repositories = $user->repositories()
            ->with(['package.owner'])
            ->whereHas('package', function ($query) {
                $query->whereNotNull('repository_url');
            })
            ->get();
        return Inertia::render('Dashboard/Repositories', [
            'repositories' => $repositories,
        ]);
    }

    public function claim($id)
    {
        $user = Auth::user();
        $repo = Repository::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Check if already claimed by anyone
        $existing = Package::where('repository_url', $repo->html_url)->first();
        if ($existing && $existing->user_id) {
            return response()->json(['error' => 'Repository already claimed as a package.'], 409);
        }

        // If package exists but not claimed, set owner_id
        if ($existing && !$existing->user_id) {
            $existing->user_id = $user->id;
            $existing->save();
        } else if (!$existing) {
            return redirect()->route('repositories.index')->dangerBanner('This repository is not a published package on packagist.org, please ensure it is a valid package before trying to claim it.');
        }

        return response()->json(['success' => true]);
    }
}
