<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Context;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Context\Type;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContextStratigraphicUnitSiteValidationTest extends KernelTestCase
{
    use ApiDataTestProviderTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testInsertWithDifferentSitesThrowsException(): void
    {
        // Create two different sites
        $site1 = new Site();
        $site1->setName('Test Site 1');
        $site1->setCode('TS1');
        $site1->setDescription('Test site 1');

        $site2 = new Site();
        $site2->setName('Test Site 2');
        $site2->setCode('TS2');
        $site2->setDescription('Test site 2');

        $this->entityManager->persist($site1);
        $this->entityManager->persist($site2);
        $this->entityManager->flush();

        // Create context for site1
        /** @var Type $contextType */
        $context = new Context();
        $context->setSite($site1);
        $context->setType('fill');
        $context->setName('test context');
        $context->setDescription('Test context description');

        // Create stratigraphic unit for site2 (different site)
        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site2);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(999);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($context);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create ContextStratigraphicUnit with different sites - should trigger exception
        $contextSU = new ContextStratigraphicUnit();
        $contextSU->setContext($context);
        $contextSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($contextSU);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Stratigraphic unit and context must belong to the same site');

        $this->entityManager->flush();
    }

    public function testUpdateWithDifferentSitesThrowsException(): void
    {
        // Create two different sites
        $site1 = new Site();
        $site1->setName('Test Site 1');
        $site1->setCode('TS1');
        $site1->setDescription('Test site 1');

        $site2 = new Site();
        $site2->setName('Test Site 2');
        $site2->setCode('TS2');
        $site2->setDescription('Test site 2');

        $this->entityManager->persist($site1);
        $this->entityManager->persist($site2);
        $this->entityManager->flush();

        // Create context and SU for same site initially
        $context1 = new Context();
        $context1->setSite($site1);
        $context1->setType('fill');
        $context1->setName('test context 1');
        $context1->setDescription('Test context 1 description');

        $context2 = new Context();
        $context2->setSite($site2);
        $context2->setType('fill');
        $context2->setName('test context 2');
        $context2->setDescription('Test context 2 description');

        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site1);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(998);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($context1);
        $this->entityManager->persist($context2);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create valid ContextStratigraphicUnit initially
        $contextSU = new ContextStratigraphicUnit();
        $contextSU->setContext($context1);
        $contextSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($contextSU);
        $this->entityManager->flush();

        // Update to use context from different site - should trigger exception
        $contextSU->setContext($context2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Stratigraphic unit and context must belong to the same site');

        $this->entityManager->flush();
    }

    public function testValidInsertWithSameSiteSucceeds(): void
    {
        // Create site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS');
        $site->setDescription('Test site');

        $this->entityManager->persist($site);
        $this->entityManager->flush();

        // Create context and SU for same site
        $context = new Context();
        $context->setSite($site);
        $context->setType('fill');
        $context->setName('test context');
        $context->setDescription('Test context description');

        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(997);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($context);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create ContextStratigraphicUnit with same site - should succeed
        $contextSU = new ContextStratigraphicUnit();
        $contextSU->setContext($context);
        $contextSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($contextSU);
        $this->entityManager->flush();

        $this->assertNotNull($contextSU->getId());
        $this->assertEquals($context->getId(), $contextSU->getContext()->getId());
        $this->assertEquals($stratigraphicUnit->getId(), $contextSU->getStratigraphicUnit()->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
