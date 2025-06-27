<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SampleUniqueConstraintSiteNumberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSampleUniqueConstraintIsEnforcedForStratigraphicUnit(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS1');
        $site->setDescription('Test site for constraint testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2024);
        $su->setNumber(1);
        $su->setDescription('Test SU');
        $this->entityManager->persist($su);

        $this->entityManager->flush();

        // Create first sample with site_id and number combination
        $sample1 = new Sample();
        $sample1->setStratigraphicUnit($su);
        $sample1->setYear(2024);
        $sample1->setNumber(100);
        $sample1->setDescription('First sample');

        $this->entityManager->persist($sample1);
        $this->entityManager->flush();

        // This should succeed - first sample persisted
        $this->assertNotNull($sample1->getId());

        // Try to create second sample with same site_id and number
        $sample2 = new Sample();
        $sample2->setStratigraphicUnit($su);
        $sample2->setYear(2024);
        $sample2->setNumber(100); // Same number as sample1
        $sample2->setDescription('Second sample - should fail');

        $this->entityManager->persist($sample2);

        // This should throw a unique constraint violation
        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testSampleUniqueConstraintIsEnforcedForContext(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS1');
        $site->setDescription('Test site for constraint testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $context = new Context();
        $context->setSite($site);
        $context->setType(0);
        $context->setName('Test Context 1');;
        $context->setDescription('Test Context 1 description');
        $this->entityManager->persist($context);

        $this->entityManager->flush();

        // Create first sample with site_id and number combination
        $sample1 = new Sample();
        $sample1->setContext($context);
        $sample1->setYear(2024);
        $sample1->setNumber(100);
        $sample1->setDescription('First sample');

        $this->entityManager->persist($sample1);
        $this->entityManager->flush();

        // This should succeed - first sample persisted
        $this->assertNotNull($sample1->getId());

        // Try to create second sample with same site_id and number
        $sample2 = new Sample();
        $sample2->setContext($context);
        $sample2->setYear(2024);
        $sample2->setNumber(100); // Same number as sample1
        $sample2->setDescription('Second sample - should fail');

        $this->entityManager->persist($sample2);

        // This should throw a unique constraint violation
        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    public function testSampleUniqueConstraintAllowsDifferentSites(): void
    {
        // Create two different sites
        $site1 = new Site();
        $site1->setName('Test Site 1');
        $site1->setCode('TS1');
        $site1->setDescription('Test site 1');
        $this->entityManager->persist($site1);

        $site2 = new Site();
        $site2->setName('Test Site 2');
        $site2->setCode('TS2');
        $site2->setDescription('Test site 2');
        $this->entityManager->persist($site2);

        // Create stratigraphic units for each site
        $su1 = new StratigraphicUnit();
        $su1->setSite($site1);
        $su1->setYear(2024);
        $su1->setNumber(1);
        $su1->setDescription('Test SU 1');
        $this->entityManager->persist($su1);

        $su2 = new StratigraphicUnit();
        $su2->setSite($site2);
        $su2->setYear(2024);
        $su2->setNumber(1);
        $su2->setDescription('Test SU 2');
        $this->entityManager->persist($su2);

        $this->entityManager->flush();

        // Create samples with same number but different sites
        $sample1 = new Sample();
        $sample1->setStratigraphicUnit($su1);
        $sample1->setYear(2024);
        $sample1->setNumber(100);
        $sample1->setDescription('Sample from site 1');

        $sample2 = new Sample();
        $sample2->setStratigraphicUnit($su2);
        $sample2->setYear(2024);
        $sample2->setNumber(100); // Same number but different site
        $sample2->setDescription('Sample from site 2');

        $this->entityManager->persist($sample1);
        $this->entityManager->persist($sample2);

        // This should succeed - different sites allow same numbers
        $this->entityManager->flush();

        $this->assertNotNull($sample1->getId());
        $this->assertNotNull($sample2->getId());
    }

    public function testSampleUniqueConstraintAllowsDifferentNumbers(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS1');
        $site->setDescription('Test site for constraint testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2024);
        $su->setNumber(1);
        $su->setDescription('Test SU');
        $this->entityManager->persist($su);

        $this->entityManager->flush();

        // Create samples with different numbers on same site
        $sample1 = new Sample();
        $sample1->setStratigraphicUnit($su);
        $sample1->setYear(2024);
        $sample1->setNumber(100);
        $sample1->setDescription('First sample');

        $sample2 = new Sample();
        $sample2->setStratigraphicUnit($su);
        $sample2->setYear(2024);
        $sample2->setNumber(101); // Different number
        $sample2->setDescription('Second sample');

        $this->entityManager->persist($sample1);
        $this->entityManager->persist($sample2);

        // This should succeed - different numbers allowed on same site
        $this->entityManager->flush();

        $this->assertNotNull($sample1->getId());
        $this->assertNotNull($sample2->getId());
    }
}

