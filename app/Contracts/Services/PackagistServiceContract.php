<?php

namespace App\Contracts\Services;

interface PackagistServiceContract
{
    public function search(string $query, int $page = 1): array;
}
