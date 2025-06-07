<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Project;
use Illuminate\Http\Request;

class Projects extends Controller
{
    public function detail(Request $request)
    {
        $packageNames = $request->get('id', null);

        return response()->json(
            Project::findOrFail($packageNames)
        );
    }
}
