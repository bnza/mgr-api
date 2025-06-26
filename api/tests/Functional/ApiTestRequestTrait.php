<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait ApiTestRequestTrait
{
    private ?ParameterBagInterface $parameterBag = null;

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

    protected function getUsers(): array
    {
        $client = self::createClient();

        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "user_admin@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.user_admin_pw"),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $userResponse = $this->apiRequest($client, 'GET', '/api/users', [
            'token' => $token,
        ]);

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    /**
     * Return the user IRI by his UUID or email
     * @param string $userIdOrEmail
     * @return ?string
     */
    protected function getUserIri(string $userIdOrEmail): ?string
    {
        $isUuid = preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $userIdOrEmail
        );
        $isEmail = filter_var($userIdOrEmail, FILTER_VALIDATE_EMAIL);

        $users = $this->getUsers();

        foreach ($users as $user) {
            if (($isUuid && $user['id'] === $userIdOrEmail) ||
                ($isEmail && $user['email'] === $userIdOrEmail)) {
                return $user['@id'];
            }
        }

        return null;
    }

    protected function getSites(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/sites');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getSiteIri(mixed $siteIdOrCode): ?string
    {
        $isId = is_numeric($siteIdOrCode);
        $isCode = is_string($siteIdOrCode);

        $sites = $this->getSites();

        foreach ($sites as $site) {
            if (($isId && $site['id'] === $siteIdOrCode) ||
                ($isCode && $site['code'] === $siteIdOrCode)) {
                return $site['@id'];
            }
        }

        return null;
    }


}
