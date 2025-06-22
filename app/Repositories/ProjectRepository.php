<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryContract
{
    public function findById($id): ?Project
    {
        return Project::find($id);
    }

    public function findByIdWithRelations($id, array $relations = []): ?Project
    {
        return Project::with($relations)->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): bool
    {
        return $project->update($data);
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function paginateForUser($userId, $ownerType, $perPage = 10, $query = null): LengthAwarePaginator
    {
        $builder = Project::query()
            ->where('owner_id', $userId)
            ->where('owner_type', $ownerType)
            ->orderByDesc('updated_at');
        if ($query) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                  ->orWhere('description', 'like', "%$query%") ;
            });
        }
        return $builder->paginate($perPage);
    }

    public function recentForUser($userId, $ownerType, $limit = 5)
    {
        return Project::query()
            ->where('owner_id', $userId)
            ->where('owner_type', $ownerType)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}

