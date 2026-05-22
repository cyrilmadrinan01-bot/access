<?php

namespace App\Services\SuccessFactors;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class SuccessFactorsClient
{
    protected Client $httpClient;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.successfactors.base_url'), '/') . '/'; 
        $this->httpClient = new Client([
    'base_uri' => $this->baseUrl,
    'auth'     => [
        config('services.successfactors.username') . '@' . config('services.successfactors.company_id'),
        config('services.successfactors.password'),
    ],
    'headers'  => [
        'Accept' => 'application/json',
    ],
]);
    }

    protected function client()
    {
        return Http::withBasicAuth(
            config('services.successfactors.username') . '@' . config('services.successfactors.company_id'),
            config('services.successfactors.password')
        )
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout(config('services.successfactors.timeout'));
    }
/*
    public function get(string $entity, array $params = [])
    {
        return $this->client()
            ->get("{$this->baseUrl}/{$entity}", $params)
            ->throw()
            ->json();
    }
*/
    public function get(string $uri, array $query = []): array
    {
        // Build query string exactly as Guzzle sends it
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $fullUrl = rtrim($this->baseUrl, '/')
            . '/'
            . ltrim($uri, '/')
            . ($queryString ? '?' . $queryString : '');

        \Illuminate\Support\Facades\Log::info('SF ODATA REQUEST URL', [
            'url' => $fullUrl,
        ]);

        $response = $this->httpClient->get($uri, [
            'query' => $query,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getByUrl(string $url)
    {
        return $this->client()
            ->get($url)
            ->throw()
            ->json();
    }

}
