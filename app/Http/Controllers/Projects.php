<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Ramsey\Uuid\Nonstandard\Uuid;

class Projects extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = Project::query()
            ->where('owner_id', $user->id)
            ->where('owner_type', User::class)
            ->get();
        return Inertia::render('Dashboard/Projects', [
            'projects' => $projects,
        ]);
    }
    public function show(Request $request, Project $project)
    {
        $project->load(['user', 'packages', 'plugins', 'themes']);
        return Inertia::render('Dashboard/Project', [
            'project' => $project,
        ]);
    }
    public function detail(Request $request)
    {
        $packageIds = $request->get('id', null);
        return response()->json(
            Project::query()->with([
                'plugins',
                'themes'
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

        return Inertia::location(route('dashboard'));;
        return redirect()->route('dashboard')->with('success', 'Project created successfully!');
    }

    public function addPlugin(Request $request, Project $project)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:marketplace_packages,id',
        ]);
        // Assuming a many-to-many relationship: $project->plugins()
        $project->plugins()->syncWithoutDetaching([$validated['id']]);
        return response()->json(['success' => true]);
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
}
