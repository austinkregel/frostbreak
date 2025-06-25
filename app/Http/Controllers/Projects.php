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
use App\Http\Requests\AddPluginRequest;
use App\Http\Requests\AddThemeRequest;
use App\Http\Requests\RemovePluginRequest;
use App\Http\Requests\RemoveThemeRequest;
use App\Http\Requests\ShowProjectRequest;
use App\Http\Requests\DetailProjectRequest;
use App\Http\Requests\StoreProjectRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\ProjectRepositoryContract;

class Projects extends Controller
{
    private const PROJECTS_PER_PAGE = 10;
    private const ERROR_NOT_FOUND = 'Project not found.';
    private const SUCCESS_UPDATED = 'Project updated successfully!';
    private const SUCCESS_DELETED = 'Project deleted successfully!';

    private ProjectRepositoryContract $projects;

    public function __construct(ProjectRepositoryContract $projects)
    {
        $this->projects = $projects;
    }

    public function show(ShowProjectRequest $request, Project $project)
    {
        $project = $this->projects->findByIdWithRelations($project->license_id, ['user', 'plugins', 'themes']);
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

        $query->whereNotIn('id', $project->plugins()->pluck('marketplace_packages.id')->concat($project->themes()->pluck('marketplace_packages.id')->toArray()));

        return $query->paginate($request->input('limit', 12));
    }

    public function detail(DetailProjectRequest $request)
    {
        $packageId = $request->get('id', null);

        if (is_numeric($packageId)) {
            return response('Invalid Project License Key; please use the UUID in the URL, not the project id', 400);
        }

        $project = $this->projects->findByIdWithRelations($packageId, ['plugins.versions', 'themes.versions']);

        if (empty($project)) {
            return response('Invalid Project License Key; project does not exist', 400);
        }

        $project->setRelation('plugins', $project->plugins->map->name);

        return response()->json($project);
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();
        $project = $this->projects->create([
            'name' => $validated['name'],
            'license_id' => Uuid::uuid4(),
            'owner' => $request->user()->name,
            'owner_id' => $request->user()->id,
            'owner_type' => get_class($request->user()),
        ]);
        return Inertia::location(route('project.show', ['project' => $project->license_id]));
    }

    public function addPlugin(AddPluginRequest $request, Project $project)
    {
        $validated = $request->validated();
        $project->plugins()->syncWithoutDetaching([$validated['id']]);
        return inertia()->location(route('project.show', ['project' => $project->license_id]));
    }

    public function addTheme(AddThemeRequest $request, Project $project)
    {
        $validated = $request->validated();
        $project->themes()->syncWithoutDetaching([$validated['id']]);
        return response()->json(['success' => true]);
    }

    public function removePlugin(RemovePluginRequest $request, Project $project)
    {
        $validated = $request->validated();
        $project->plugins()->detach($validated['id']);
        return response()->json(['success' => true]);
    }

    public function removeTheme(RemoveThemeRequest $request, Project $project)
    {
        $validated = $request->validated();
        $project->themes()->detach($validated['id']);
        return response()->json(['success' => true]);
    }

    public function list(Request $request)
    {
        $projects = $this->projects->paginateForUser(
            $request->user()?->id,
            User::class,
            self::PROJECTS_PER_PAGE,
            $request->get('query')
        );
        return Inertia::render('Dashboard/Projects', [
            'projects' => $projects
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->projects->update($project, [
            'name' => $request->validated()['name']
        ]);
        return inertia()->location(route('dashboard'))->with('success', self::SUCCESS_UPDATED);
    }

    public function destroy(DeleteProjectRequest $request, Project $project)
    {
        $this->projects->delete($project);
        return inertia()->location(route('dashboard'))->with('success', self::SUCCESS_DELETED);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $recentProjects = $this->projects->recentForUser($user->id, User::class, 5);
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
        $projects = $this->projects->paginateForUser($user->id, User::class, $perPage, $query);
        return response()->json($projects);
    }
}
