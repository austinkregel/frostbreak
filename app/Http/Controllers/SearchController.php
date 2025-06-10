<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Package;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query', '');
        $filter = $request->input('filter', 'all');
        $sortBy = $request->input('sortBy', 'downloads');
        $sortDirection = $request->input('direction', 'desc');

        $builder = Package::search($query);
        if ($filter !== 'all') {
            $builder->where('type', $filter === 'plugins' ? 'plugin' : 'theme');
        }
        if ($sortBy !== 'relevance') {
            $builder->orderBy($sortBy, $sortDirection);
        }
        $results = $builder->paginate(
            $request->input('limit', 12),
            'page',
            $request->input('page', 1)
        )->withQueryString();

        $user = $request->user();
        $projects = $user ? $user->projects()->orderByDesc('updated_at')->get() : collect();
        return Inertia::render('Dashboard/Search', [
            'results' => $results,
            'query' => $query,
            'filter' => $filter,
            'direction' => $sortDirection,
            'sortBy' => $sortBy,
            'projects' => $projects,
        ]);
    }
}
