<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait ApiTestRequestTrait
{
    /**
     * Make an API request with optional authentication
     *
     * @param Client $client
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $url The API endpoint URL
     * @param array $options Request options (json, headers, token, etc.)
     * @return ResponseInterface
     */
    protected function apiRequest(Client $client, string $method, string $url, array $options = []): ResponseInterface
    {
        // Handle authentication token
        if (isset($options['token'])) {
            $authHeader = ['Authorization' => "Bearer {$options['token']}"];

            if (isset($options['headers'])) {
                $options['headers'] = array_merge($authHeader, $options['headers']);
            } else {
                $options['headers'] = $authHeader;
            }

            // Remove token from options since it's now in headers
            unset($options['token']);
        }

        // Handle Content-Type
        if (
            in_array(strtoupper($method), ['POST', 'PUT'])
            && !isset($options['headers']['Content-Type'])
        ) {
            $options['headers']['Content-Type'] = 'application/ld+json';
        }
        if (
            in_array(strtoupper($method), ['PATCH'])
            && !isset($options['headers']['Content-Type'])
        ) {
            $options['headers']['Content-Type'] = 'application/merge-patch+json';
        }

        return $client->request($method, $url, $options);
    }
}
