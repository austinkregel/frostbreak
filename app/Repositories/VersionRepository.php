<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Version;
use Illuminate\Support\Facades\Log;

class VersionRepository
{
    public function updateOrCreate(array $attributes, array $values = []): Version
    {
        return Version::updateOrCreate($attributes, $values);
    }

    /**
     * Filter versions based on domain rules (e.g., stability, keywords).
     * Logs filtered out or unknown cases for observability.
     */
    public function filterVersions(array $versions): array
    {
        $filtered = [];
        foreach ($versions as $version) {
            if ($this->shouldIncludeVersion($version)) {
                $filtered[] = $version;
            } else {
                Log::info('Version filtered out', ['version' => $version]);
            }
        }
        return $filtered;
    }

    /**
     * Determine if a version should be included (customize domain logic here).
     */
    protected function shouldIncludeVersion(array $version): bool
    {
        // Example: filter out dev versions
        if (isset($version['version']) && str_starts_with($version['version'], 'dev')) {
            return false;
        }
        // Add more domain-specific rules as needed
        return true;
    }

    /**
     * Log unknown cases for observability.
     */
    public function logUnknownCase(array $context): void
    {
        Log::error('Unknown version case encountered', $context);
    }
}
