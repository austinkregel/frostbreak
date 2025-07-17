<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class PrivacyPolicyController extends Controller
{
    public function show()
    {
        $markdown = File::get(resource_path('views/privacy-policy.md'));
        return Inertia::render('PrivacyPolicy', [
            'markdown' => $markdown,
        ]);
    }
}

