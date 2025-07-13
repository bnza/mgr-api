<?php

namespace App\Tests\Functional\Service\Validator;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Site;
use App\Service\Validator\ResourceUniqueValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV4;

class ResourceUniqueValidatorTest extends KernelTestCase
{
    private ResourceUniqueValidator $validator;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        // Get the EntityManager from the container
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        // Create the validator instance manually
        $this->validator = new ResourceUniqueValidator($this->entityManager);
    }

    public function testIsUniqueReturnsFalseForExistingCode(): void
    {
        // Get the first site from database
        $site = $this->entityManager
            ->getRepository(Site::class)
            ->findOneBy([]);

        $this->assertNotNull($site, 'Should have at least one Site in the database');

        $result = $this->validator->isUnique(Site::class, ['code' => $site->getCode()]);

        $this->assertFalse($result, 'Should return false when code already exists');
    }

    public function testIsUniqueReturnsFalseForExistingName(): void
    {
        // Get the first site from database
        $site = $this->entityManager
            ->getRepository(Site::class)
            ->findOneBy([]);

        $this->assertNotNull($site, 'Should have at least one Site in the database');

        $result = $this->validator->isUnique(Site::class, ['name' => $site->getName()]);

        $this->assertFalse($result, 'Should return false when name already exists');
    }

    public function testIsUniqueReturnsTrueForNewCode(): void
    {
        $result = $this->validator->isUnique(Site::class, ['code' => 'NW']);

        $this->assertTrue($result, 'Should return true when code does not exist');
    }

    public function testIsUniqueReturnsTrueForNewName(): void
    {
        $result = $this->validator->isUnique(Site::class, ['name' => 'New Site']);

        $this->assertTrue($result, 'Should return true when name does not exist');
    }

    public function testIsUniqueReturnsFalseForResourceUserExistingName(): void
    {
        // Get the first site from database
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([]);

        $this->assertNotNull($user, 'Should have at least one Site in the database');

        $result = $this->validator->isUnique(User::class, ['email' => $user->getEmail()]);

        $this->assertFalse($result, 'Should return false when name already exists');
    }

    public function testIsUniqueReturnsTrueForResourceUserNewEmail(): void
    {
        $result = $this->validator->isUnique(User::class, ['email' => 'new@example.com']);

        $this->assertTrue($result, 'Should return true when email does not exist');
    }

    public function testIsUniqueReturnsFalseForExistingSiteUserPrivilege(): void
    {
        // Get the first SiteUserPrivilege from the database
        $siteUserPrivilege = $this->entityManager
            ->getRepository(SiteUserPrivilege::class)
            ->findOneBy([]);

        $this->assertNotNull($siteUserPrivilege, 'Should have at least one SiteUserPrivilege in the database');

        $result = $this->validator->isUnique(SiteUserPrivilege::class, [
            'site' => $siteUserPrivilege->getSite()->getId(),
            'user' => $siteUserPrivilege->getUser()->getId(),
        ]);

        $this->assertFalse($result, 'Should return false when site-user combination already exists');
    }

    public function testIsUniqueReturnsTrueForNewSiteUserPrivilege(): void
    {
        // Use non-existent IDs that are very unlikely to exist in the database
        $nonExistentSiteId = 999999;
        $nonExistentUserId = new UuidV4();

        $result = $this->validator->isUnique(SiteUserPrivilege::class, [
            'site' => $nonExistentSiteId,
            'user' => $nonExistentUserId,
        ]);

        $this->assertTrue($result, 'Should return true when site-user combination does not exist');
    }

    public function testIsUniqueThrowsExceptionForUnsupportedResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource "App\Entity\UnsupportedEntity" is not supported.');

        $this->validator->isUnique('App\Entity\UnsupportedEntity', ['code' => 'test']);
    }

    public function testIsUniqueThrowsExceptionForUnsupportedCriteria(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Resource ".*" does not support criteria "unsupported_field"\./');

        $this->validator->isUnique(Site::class, ['unsupported_field' => 'test']);
    }
}
