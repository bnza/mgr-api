<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Pottery;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Pottery\FunctionalForm;
use App\Entity\Vocabulary\Pottery\FunctionalGroup;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PotterySiteUniqueValidationTest extends KernelTestCase
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

    private function createSite(string $name, string $code): Site
    {
        $site = new Site();
        $site->setName($name);
        $site->setCode($code);
        $site->setDescription("Description for $name");
        $this->entityManager->persist($site);

        return $site;
    }

    private function createSU(Site $site, int $number): StratigraphicUnit
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

    public function testInsertDuplicateInventoryInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test Site 1', 'TS1');
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

    public function testInsertSameInventoryInDifferentSitesSucceeds(): void
    {
        $site1 = $this->createSite('Test Site 1', 'TS1');
        $site2 = $this->createSite('Test Site 2', 'TS2');

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

    public function testUpdateDuplicateInventoryInSameSiteThrowsException(): void
    {
        $site = $this->createSite('Test Site 1', 'TS1');
        $su1 = $this->createSU($site, 101);
        $su2 = $this->createSU($site, 102);

        $this->entityManager->flush();

        $pottery1 = $this->createPottery($su1, 'INV001');
        $pottery2 = $this->createPottery($su2, 'INV002');
        $this->entityManager->flush();

        // Update pottery2 to have INV001, which exists in same site
        $pottery2->setInventory('INV001');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pottery inventory INV001 must be unique within the same site');

        $this->entityManager->flush();
    }

    public function testUpdateOwnInventorySucceeds(): void
    {
        $site = $this->createSite('Test Site 1', 'TS1');
        $su = $this->createSU($site, 101);

        $this->entityManager->flush();

        $pottery = $this->createPottery($su, 'INV001');
        $this->entityManager->flush();

        // Update same inventory - should not trigger trigger (NEW.id != p.id check)
        $pottery->setInventory('INV001');
        $this->entityManager->flush();

        $this->assertEquals('INV001', $pottery->getInventory());
    }

    public function testUpdateSUToAnotherSiteWithDuplicateInventoryThrowsException(): void
    {
        $site1 = $this->createSite('Test Site 1', 'TS1');
        $site2 = $this->createSite('Test Site 2', 'TS2');

        $this->entityManager->flush();

        $su1 = $this->createSU($site1, 101);
        $su2 = $this->createSU($site2, 201);

        $this->entityManager->flush();

        $pottery1 = $this->createPottery($su1, 'INV001');
        $pottery2 = $this->createPottery($su2, 'INV001'); // Allowed as different sites
        $this->entityManager->flush();

        // Try to move pottery2 to site1 where INV001 already exists
        $pottery2->setStratigraphicUnit($su1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pottery inventory INV001 must be unique within the same site');

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
