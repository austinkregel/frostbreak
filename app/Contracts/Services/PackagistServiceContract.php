<?php

namespace App\Contracts\Services;

interface PackagistServiceContract
{
    public function search(string $query, int $limit = 15, int $page = 1): array;
}
