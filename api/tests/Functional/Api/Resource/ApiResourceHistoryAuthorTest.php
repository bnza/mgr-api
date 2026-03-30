<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Auth\User;
use App\Entity\Vocabulary\History\Author;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceHistoryAuthorTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testGetCollectionIsAllowedForAnonymousUser(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors');
        $this->assertSame(200, $response->getStatusCode());

        $collection = $response->toArray();
        $this->assertArrayHasKey('member', $collection);
        // Based on VocHistoryVoter, READ is always true.
    }

    public function testPostGetCollectionWholeAclReturnsFalseForUnauthenticatedUser(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors');
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }

    public function testPostAuthorIsAllowedForUserWithEditorAndHistorianRoles(): void
    {
        $client = self::createClient();

        // Programmatically generate a user with both required roles
        $email = 'editor_historian@example.com';
        $password = 'TestPassword123!';
        $this->createUserWithRoles($email, $password, ['ROLE_EDITOR', 'ROLE_HISTORIAN']);

        $token = $this->getUserToken($client, $email, $password);

        $payload = [
            'value' => 'New Author '.uniqid(),
            'variant' => 'Author Variant',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/vocabulary/history/authors', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($payload['value'], $response->toArray()['value']);
    }

    public function testPostAuthorIsDeniedForUserWithOnlyEditorRole(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $payload = [
            'value' => 'Another Author '.uniqid(),
        ];

        $response = $this->apiRequest($client, 'POST', '/api/vocabulary/history/authors', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteAuthorIsAllowedForUserWithEditorAndHistorianRoles(): void
    {
        $client = self::createClient();

        $email = 'deleter@example.com';
        $password = 'TestPassword123!';
        $this->createUserWithRoles($email, $password, ['ROLE_EDITOR', 'ROLE_HISTORIAN']);
        $token = $this->getUserToken($client, $email, $password);

        // First create an author to delete
        $author = new Author();
        $author->value = 'Author to delete '.uniqid();

        $manager = self::getContainer()->get('doctrine')->getManager();
        $manager->persist($author);
        $manager->flush();

        $authorId = $author->id;

        $response = $this->apiRequest($client, 'DELETE', "/api/vocabulary/history/authors/$authorId", [
            'token' => $token,
        ]);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteAuthorIsDeniedForUnauthorizedUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_base');

        // Create an author
        $author = new Author();
        $author->value = 'Safe Author '.uniqid();

        $manager = self::getContainer()->get('doctrine')->getManager();
        $manager->persist($author);
        $manager->flush();

        $authorId = $author->id;

        $response = $this->apiRequest($client, 'DELETE', "/api/vocabulary/history/authors/$authorId", [
            'token' => $token,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    private function createUserWithRoles(string $email, string $password, array $roles): void
    {
        $container = self::getContainer();
        $manager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setEnabled(true);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $manager->persist($user);
        $manager->flush();
    }
}
