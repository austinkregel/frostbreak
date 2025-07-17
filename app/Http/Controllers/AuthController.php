<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Repository;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function redirectToGithub()
    {
        return Socialite::driver('github')
            ->scopes([
                'user:email', // Request access to the user's email
                'repo', // Request access to repositories
            ])
            ->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['msg' => 'GitHub authentication failed.']);
        }

        if (!auth()->check()) {
            // If not authenticated, create or update the user
            if (!User::where('github_id', $githubUser->id)->exists()) {
                return redirect('/')->withErrors(['msg' => 'User not found.']);
            } // If the user is already authenticated, update their GitHub token
        }
        $user = auth()->user();
        $user->update(['github_token' => $githubUser->token]);

        // Fetch and index GitHub repositories
        $this->syncGithubRepositories($user, $githubUser->token);

        return redirect('/dashboard');
    }

    protected function syncGithubRepositories(User $user, $token)
    {
        $page = 1;
        $repos = [];
        do {
            $response = Http::withToken($token)
                ->get('https://api.github.com/user/repos', [
                    'per_page' => 100,
                    'page' => $page,
                    'affiliation' => 'owner,collaborator,organization_member',
                ]);
            if ($response->failed()) break;
            $data = $response->json();
            foreach ($data as $repo) {
                Repository::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'github_repo_id' => $repo['id'],
                    ],
                    [
                        'name' => $repo['name'],
                        'full_name' => $repo['full_name'],
                        'html_url' => $repo['html_url'],
                        'description' => $repo['description'],
                        'private' => $repo['private'],
                        'default_branch' => $repo['default_branch'],
                    ]
                );
            }
            $repos = $data;
            $page++;
        } while (count($repos) === 100);
    }
}
