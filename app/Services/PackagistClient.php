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

    public function search($query, $page = 1)
    {
        $url = $this->baseUrl . '/search.json';
        $http = Http::withHeaders($this->getAuthHeaders());
        $response = $http->get($url, [
            'q' => $query,
            'page' => $page,
        ]);
        if (!$response->successful()) {
            \Log::error('Failed to fetch package from Packagist', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }
        return $response->json();
    }

    public function getPackage($vendor, $package)
    {
        $url = $this->baseUrl . "/packages/{$vendor}/{$package}.json";
        $http = Http::withHeaders($this->getAuthHeaders());
        $response = $http->get($url);
        if (!$response->successful()) {
            \Log::error('Failed to fetch package from Packagist', [
                'vendor' => $vendor,
                'package' => $package,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }
        return $response->json();
    }

    protected function getAuthHeaders(): array
    {
        return $this->apiKey ? ['Authorization' => 'Bearer ' . $this->apiKey] : [];
    }
}
