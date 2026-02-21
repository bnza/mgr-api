<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\ArchaeologicalSite;
use App\Entity\Data\Individual;
use App\Entity\Data\Pottery;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Pottery\FunctionalForm;
use App\Entity\Vocabulary\Pottery\FunctionalGroup;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SiteRelatedUniqueValidationTest extends KernelTestCase
{
    use ApiDataTestProviderTrait;

    private EntityManagerInterface $entityManager;
    private FunctionalGroup $functionalGroup;
    private FunctionalForm $functionalForm;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        // Create common vocabulary items
        $this->functionalGroup = new FunctionalGroup();
        $this->functionalGroup->value = 'Tableware';
        $this->entityManager->persist($this->functionalGroup);

        $this->functionalForm = new FunctionalForm();
        $this->functionalForm->value = 'Bowl';
        $this->entityManager->persist($this->functionalForm);

        $this->entityManager->flush();
    }

    private function createSite(string $name, string $code): ArchaeologicalSite
    {
        $site = new ArchaeologicalSite();
        $site->setName($name);
        $site->setCode($code);
        $site->setDescription("Description for $name");
        $this->entityManager->persist($site);

        return $site;
    }

    private function createSU(ArchaeologicalSite $site, int $number): StratigraphicUnit
    {
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2026);
        $su->setNumber($number);
        $su->setDescription("Description for SU $number");
        $su->setInterpretation("Interpretation for SU $number");
        $this->entityManager->persist($su);

        return $su;
    }

    private function createPottery(StratigraphicUnit $su, string $inventory): Pottery
    {
        $pottery = new Pottery();
        $pottery->setStratigraphicUnit($su);
        $pottery->setInventory($inventory);
        $pottery->setFunctionalGroup($this->functionalGroup);
        $pottery->setFunctionalForm($this->functionalForm);
        $this->entityManager->persist($pottery);

        return $pottery;
    }

    private function createIndividual(StratigraphicUnit $su, string $identifier): Individual
    {
        $individual = new Individual();
        $individual->setStratigraphicUnit($su);
        $individual->setIdentifier($identifier);
        $this->entityManager->persist($individual);

        return $individual;
    }

    public function testInsertDuplicatePotteryInventoryInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su1 = $this->createSU($site, 101);
        $su2 = $this->createSU($site, 102);

        $this->entityManager->flush();

        $this->createPottery($su1, 'INV001');
        $this->entityManager->flush();

        // Second pottery in same site with same inventory
        $this->createPottery($su2, 'INV001');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pottery inventory INV001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testInsertSamePotteryInventoryInDifferentSitesSucceeds(): void
    {
        $site1 = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $site2 = $this->createSite('Test ArchaeologicalSite 2', 'TS2');

        $this->entityManager->flush();

        $su1 = $this->createSU($site1, 101);
        $su2 = $this->createSU($site2, 201);

        $this->entityManager->flush();

        $pottery1 = $this->createPottery($su1, 'INV001');
        $this->entityManager->flush();

        $pottery2 = $this->createPottery($su2, 'INV001');
        $this->entityManager->flush();

        $this->assertNotNull($pottery1->getId());
        $this->assertNotNull($pottery2->getId());
        $this->assertNotEquals($pottery1->getId(), $pottery2->getId());
    }

    public function testInsertDuplicateIndividualIdentifierInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su1 = $this->createSU($site, 101);
        $su2 = $this->createSU($site, 102);

        $this->entityManager->flush();

        $this->createIndividual($su1, 'IND001');
        $this->entityManager->flush();

        // Second individual in same site with same identifier
        $this->createIndividual($su2, 'IND001');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Individual identifier IND001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testInsertSameIndividualIdentifierInDifferentSitesSucceeds(): void
    {
        $site1 = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $site2 = $this->createSite('Test ArchaeologicalSite 2', 'TS2');

        $this->entityManager->flush();

        $su1 = $this->createSU($site1, 101);
        $su2 = $this->createSU($site2, 201);

        $this->entityManager->flush();

        $individual1 = $this->createIndividual($su1, 'IND001');
        $this->entityManager->flush();

        $individual2 = $this->createIndividual($su2, 'IND001');
        $this->entityManager->flush();

        $this->assertNotNull($individual1->getId());
        $this->assertNotNull($individual2->getId());
        $this->assertNotEquals($individual1->getId(), $individual2->getId());
    }

    public function testUpdateDuplicatePotteryInventoryInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su1 = $this->createSU($site, 101);
        $su2 = $this->createSU($site, 102);

        $this->entityManager->flush();

        $this->createPottery($su1, 'INV001');
        $pottery2 = $this->createPottery($su2, 'INV002');
        $this->entityManager->flush();

        // Update pottery2 to have INV001, which exists in same site
        $pottery2->setInventory('INV001');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pottery inventory INV001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testUpdateDuplicateIndividualIdentifierInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su1 = $this->createSU($site, 101);
        $su2 = $this->createSU($site, 102);

        $this->entityManager->flush();

        $this->createIndividual($su1, 'IND001');
        $individual2 = $this->createIndividual($su2, 'IND002');
        $this->entityManager->flush();

        // Update individual2 to have IND001, which exists in same site
        $individual2->setIdentifier('IND001');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Individual identifier IND001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testUpdateOwnPotteryInventorySucceeds(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su = $this->createSU($site, 101);

        $this->entityManager->flush();

        $pottery = $this->createPottery($su, 'INV001');
        $this->entityManager->flush();

        // Update same inventory - should not trigger (NEW.id != p.id check)
        $pottery->setInventory('INV001');
        $this->entityManager->flush();

        $this->assertEquals('INV001', $pottery->getInventory());
    }

    public function testUpdateOwnIndividualIdentifierSucceeds(): void
    {
        $site = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $su = $this->createSU($site, 101);

        $this->entityManager->flush();

        $individual = $this->createIndividual($su, 'IND001');
        $this->entityManager->flush();

        // Update same identifier - should not trigger
        $individual->setIdentifier('IND001');
        $this->entityManager->flush();

        $this->assertEquals('IND001', $individual->getIdentifier());
    }

    public function testUpdatePotterySUToAnotherSiteWithDuplicateInventoryThrowsException(): void
    {
        $site1 = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $site2 = $this->createSite('Test ArchaeologicalSite 2', 'TS2');

        $this->entityManager->flush();

        $su1 = $this->createSU($site1, 101);
        $su2 = $this->createSU($site2, 201);

        $this->entityManager->flush();

        $this->createPottery($su1, 'INV001');
        $pottery2 = $this->createPottery($su2, 'INV001'); // Allowed as different sites
        $this->entityManager->flush();

        // Try to move pottery2 to site1 where INV001 already exists
        $pottery2->setStratigraphicUnit($su1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pottery inventory INV001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testUpdateIndividualSUToAnotherSiteWithDuplicateIdentifierThrowsException(): void
    {
        $site1 = $this->createSite('Test ArchaeologicalSite 1', 'TS1');
        $site2 = $this->createSite('Test ArchaeologicalSite 2', 'TS2');

        $this->entityManager->flush();

        $su1 = $this->createSU($site1, 101);
        $su2 = $this->createSU($site2, 201);

        $this->entityManager->flush();

        $this->createIndividual($su1, 'IND001');
        $individual2 = $this->createIndividual($su2, 'IND001'); // Allowed as different sites
        $this->entityManager->flush();

        // Try to move individual2 to site1 where IND001 already exists
        $individual2->setStratigraphicUnit($su1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Individual identifier IND001 must be unique within the same site');

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
