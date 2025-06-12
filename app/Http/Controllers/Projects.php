<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Ramsey\Uuid\Nonstandard\Uuid;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\DeleteProjectRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class Projects extends Controller
{
    private const PROJECTS_PER_PAGE = 10;
    private const ERROR_NOT_FOUND = 'Project not found.';
    private const SUCCESS_UPDATED = 'Project updated successfully!';
    private const SUCCESS_DELETED = 'Project deleted successfully!';

    public function show(Request $request, Project $project)
    {
        $project->load(['user', 'plugins', 'themes']);

        return Inertia::render('Dashboard/Project', [
            'project' => $project,
            'themeSearchResults' => $this->search('themeSearch', $request, $project),
            'pluginSearchResults' => $this->search('pluginSearch', $request, $project),
            'pluginQuery' => $request->input('themeSearch', ''),
        ]);
    }

    protected function search(string $query, Request $request,  Project $project): LengthAwarePaginator
    {
        if (!$request->has($query)) {
            return new LengthAwarePaginator([], 0, self::PROJECTS_PER_PAGE, 1);
        }

        $query = Package::search($request->input($query));

        $query->whereNotIn('id', $project->plugins()->pluck('marketplace_packages.id')->concat($project->themes()->pluck('marketplace_packages.id')));

        return $query->paginate(
            $request->input('limit', 12),
            'page',
        );
    }


    public function detail(Request $request)
    {
        $packageIds = $request->get('id', null);
        return response()->json(
            Project::query()->with([
                'plugins.versions',
                'themes.versions'
            ])->findOrFail($packageIds)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $project = $request->user()->projects()->create([
            'name' => $validated['name'],
            'license_id' => Uuid::uuid4(),
            'owner' => $request->user()->name,
            'owner_id' => $request->user()->id,
            'owner_type' => get_class($request->user()),
        ]);

        return Inertia::location(route('project.show', ['project' => $project->id]));
    }

    public function addPlugin(Request $request, Project $project)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:marketplace_packages,id',
        ]);
        // Assuming a many-to-many relationship: $project->plugins()
        $project->plugins()->syncWithoutDetaching([$validated['id']]);
        return inertia()->location(route('project.show', ['project' => $project->id]));
    }

    public function addTheme(Request $request, Project $project)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:marketplace_packages,id',
        ]);
        // Assuming a many-to-many relationship: $project->themes()
        $project->themes()->syncWithoutDetaching([$validated['id']]);
        return response()->json(['success' => true]);
    }

    public function removePlugin(Request $request, Project $project)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:marketplace_packages,id',
        ]);
        // Detach the plugin from the project
        $project->plugins()->detach($validated['id']);
        return response()->json(['success' => true]);
    }

    public function removeTheme(Request $request, Project $project)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:marketplace_packages,id',
        ]);
        // Detach the theme from the project
        $project->themes()->detach($validated['id']);
        return response()->json(['success' => true]);
    }

    public function list(Request $request)
    {
        $projects = ($request->has('query') ? Project::search($request->get('query')) : Project::query())
            ->whereOwnerId($request->user()?->id)
            ->whereOwnerType(User::class)
            ->orderByDesc('updated_at')
            ->paginate()
            ->withQueryString();

        return Inertia::render('Dashboard/Projects', [
            'projects' => $projects
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->name = $request->validated()['name'];
        $project->save();
        return inertia()->location(route('dashboard'))->with('success', self::SUCCESS_UPDATED);
    }

    public function destroy(DeleteProjectRequest $request, Project $project)
    {
        $project->delete();
        return inertia()->location(route('dashboard'))->with('success', self::SUCCESS_DELETED);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $recentProjects = Project::query()
            ->where('owner_id', $user->id)
            ->where('owner_type', User::class)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
        return Inertia::render('Dashboard/Dashboard', [
            'recentProjects' => $recentProjects,
            'auth' => [
                'user' => $user,
            ],
        ]);
    }

    public function searchApi(Request $request)
    {
        $user = $request->user();
        $query = $request->input('query', '');
        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $projectsQuery = Project::query()
            ->where('owner_id', $user->id)
            ->where('owner_type', User::class);

        if ($query) {
            $projectsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                  ->orWhere('description', 'like', "%$query%") ;
            });
        }

        $projects = $projectsQuery
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($projects);
    }
}
