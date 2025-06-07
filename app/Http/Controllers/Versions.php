<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Illuminate\Http\Request;

class Versions extends Controller
{
    public function index(Request $request)
    {
        $versions = Version::query()->paginate(20);
        return response()->json($versions);
    }

    public function show($id)
    {
        $version = Version::findOrFail($id);
        return response()->json($version);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'semantic_version' => 'required|string',
            'extra' => 'nullable|array',
            'requires' => 'nullable|array',
            'requires_dev' => 'nullable|array',
            'suggests' => 'nullable|array',
            'time' => 'nullable|string',
            'provides' => 'nullable|array',
            'conflicts' => 'nullable|array',
            'replaces' => 'nullable|array',
            'tags' => 'nullable|array',
            'installation_commands' => 'nullable|array',
            'description' => 'nullable|string',
            'hash' => 'nullable|string',
            'license' => 'nullable|string',
            'package_id' => 'required|integer|exists:packages,id',
        ]);
        $version = Version::create($data);
        return response()->json($version, 201);
    }

    public function update(Request $request, $id)
    {
        $version = Version::findOrFail($id);
        $data = $request->validate([
            'semantic_version' => 'sometimes|required|string',
            'extra' => 'nullable|array',
            'requires' => 'nullable|array',
            'requires_dev' => 'nullable|array',
            'suggests' => 'nullable|array',
            'time' => 'nullable|string',
            'provides' => 'nullable|array',
            'conflicts' => 'nullable|array',
            'replaces' => 'nullable|array',
            'tags' => 'nullable|array',
            'installation_commands' => 'nullable|array',
            'description' => 'nullable|string',
            'hash' => 'nullable|string',
            'license' => 'nullable|string',
            'package_id' => 'sometimes|required|integer|exists:packages,id',
        ]);
        $version->update($data);
        return response()->json($version);
    }

    public function destroy($id)
    {
        $version = Version::findOrFail($id);
        $version->delete();
        return response()->json(['message' => 'Version deleted']);
    }
}
