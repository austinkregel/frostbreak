<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryContract
{
    public function findById($id): ?Project;
    public function findByIdWithRelations($id, array $relations = []): ?Project;
    public function create(array $data): Project;
    public function update(Project $project, array $data): bool;
    public function delete(Project $project): bool;
    public function paginateForUser($userId, $ownerType, $perPage = 10, $query = null): LengthAwarePaginator;
    public function recentForUser($userId, $ownerType, $limit = 5);
}

