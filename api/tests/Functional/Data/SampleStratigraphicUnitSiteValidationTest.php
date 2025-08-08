<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Sample\Type as SampleType;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SampleStratigraphicUnitSiteValidationTest extends KernelTestCase
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

        // Create sample for site1
        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample = new Sample();
        $sample->setSite($site1);
        $sample->setType($sampleType);
        $sample->setYear(2025);
        $sample->setNumber(999);
        $sample->setDescription('Test sample');

        // Create stratigraphic unit for site2 (different site)
        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site2);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(999);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($sample);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create SampleStratigraphicUnit with different sites - should trigger exception
        $sampleSU = new SampleStratigraphicUnit();
        $sampleSU->setSample($sample);
        $sampleSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($sampleSU);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sample and stratigraphic unit must belong to the same site');

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

        // Create sample and SU for same site initially
        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample1 = new Sample();
        $sample1->setSite($site1);
        $sample1->setType($sampleType);
        $sample1->setYear(2025);
        $sample1->setNumber(998);
        $sample1->setDescription('Test sample 1');

        $sample2 = new Sample();
        $sample2->setSite($site2);
        $sample2->setType($sampleType);
        $sample2->setYear(2025);
        $sample2->setNumber(997);
        $sample2->setDescription('Test sample 2');

        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site1);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(998);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($sample1);
        $this->entityManager->persist($sample2);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create valid SampleStratigraphicUnit initially
        $sampleSU = new SampleStratigraphicUnit();
        $sampleSU->setSample($sample1);
        $sampleSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($sampleSU);
        $this->entityManager->flush();

        // Update to use sample from different site - should trigger exception
        $sampleSU->setSample($sample2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sample and stratigraphic unit must belong to the same site');

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

        // Create sample and SU for same site
        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample = new Sample();
        $sample->setSite($site);
        $sample->setType($sampleType);
        $sample->setYear(2025);
        $sample->setNumber(996);
        $sample->setDescription('Test sample');

        $stratigraphicUnit = new StratigraphicUnit();
        $stratigraphicUnit->setSite($site);
        $stratigraphicUnit->setYear(2025);
        $stratigraphicUnit->setNumber(996);
        $stratigraphicUnit->setDescription('Test SU');
        $stratigraphicUnit->setInterpretation('test interpretation');

        $this->entityManager->persist($sample);
        $this->entityManager->persist($stratigraphicUnit);
        $this->entityManager->flush();

        // Create SampleStratigraphicUnit with same site - should succeed
        $sampleSU = new SampleStratigraphicUnit();
        $sampleSU->setSample($sample);
        $sampleSU->setStratigraphicUnit($stratigraphicUnit);

        $this->entityManager->persist($sampleSU);
        $this->entityManager->flush();

        $this->assertNotNull($sampleSU->getId());
        $this->assertEquals($sample->getId(), $sampleSU->getSample()->getId());
        $this->assertEquals($stratigraphicUnit->getId(), $sampleSU->getStratigraphicUnit()->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
