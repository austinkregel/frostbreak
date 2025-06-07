<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PackagistClient
{
    protected $baseUrl = 'https://packagist.org';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.packagist.api_key') ?? env('PACKAGIST_API_KEY');
    }

    /**
     * Search for packages on Packagist.
     *
     * @param string $query
     * @param int $page
     * @return array|null
     */
    public function search($query, $page = 1)
    {
        $url = $this->baseUrl . '/search.json';
        $http = Http::withHeaders($this->getAuthHeaders());
        $response = $http->get($url, [
            'q' => $query,
            'page' => $page,
        ]);
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

    /**
     * Get package metadata from Packagist.
     *
     * @param string $vendor
     * @param string $package
     * @return array|null
     */
    public function getPackage($vendor, $package)
    {
        $url = $this->baseUrl . "/packages/{$vendor}/{$package}.json";
        $http = Http::withHeaders($this->getAuthHeaders());
        $response = $http->get($url);
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

    protected function getAuthHeaders(): array
    {
        return $this->apiKey ? ['Authorization' => 'Bearer ' . $this->apiKey] : [];
    }
}
