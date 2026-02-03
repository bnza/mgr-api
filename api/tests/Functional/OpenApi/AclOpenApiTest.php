<?php

namespace App\Tests\Functional\OpenApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;

class AclOpenApiTest extends ApiTestCase
{
    use ApiTestRequestTrait;

    public function testAclPropertyInOpenApiSchema(): void
    {
        $client = self::createClient();

        // Request the OpenAPI specification
        $response = $client->request('GET', '/api/docs.jsonopenapi', [
            'headers' => [
                'Accept' => 'application/vnd.openapi+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openapi+json; charset=utf-8');

        $openApiSpec = $response->toArray();
        $this->assertArrayHasKey('components', $openApiSpec);
        $this->assertArrayHasKey('schemas', $openApiSpec['components']);

        $schemas = $openApiSpec['components']['schemas'];

        foreach ($schemas as $key => $schema) {
            if (!str_contains($key, '.acl.read')) {
                continue;
            }

            $atLeastOneSchemaChecked = true;
            $this->assertArrayHasKey('properties', $schema, "Schema for $key should have properties");
            $this->assertArrayHasKey('_acl', $schema['properties'], "Schema for $key should have _acl property");

            $aclSchema = $schema['properties']['_acl'];
            $this->assertEquals('object', $aclSchema['type'], "The _acl property in $key should be an object");
            $this->assertTrue($aclSchema['readOnly'], "The _acl property in $key should be readOnly");

            $this->assertArrayHasKey('properties', $aclSchema);
            $this->assertArrayHasKey('canRead', $aclSchema['properties']);
            $this->assertArrayHasKey('canUpdate', $aclSchema['properties']);
            $this->assertArrayHasKey('canDelete', $aclSchema['properties']);

            $this->assertEquals('boolean', $aclSchema['properties']['canRead']['type']);
            $this->assertEquals('boolean', $aclSchema['properties']['canUpdate']['type']);
            $this->assertEquals('boolean', $aclSchema['properties']['canDelete']['type']);

            $this->assertArrayHasKey('required', $aclSchema);
            $this->assertContains('canRead', $aclSchema['required']);
            $this->assertContains('canUpdate', $aclSchema['required']);
            $this->assertContains('canDelete', $aclSchema['required']);
        }

        $this->assertTrue($atLeastOneSchemaChecked, 'At least one resource schema from AclResourceList should be present in OpenAPI');
    }
}
