<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SampleReferenceExclusivityTriggerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testTriggerAllowsSampleWithOnlyStratigraphicUnit(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS1');
        $site->setDescription('Test site for trigger testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2024);
        $su->setNumber(1);
        $su->setDescription('Test SU');
        $this->entityManager->persist($su);

        $this->entityManager->flush();

        // Create sample with only stratigraphic unit
        $sample = new Sample();
        $sample->setStratigraphicUnit($su);
        $sample->setYear(2024);
        $sample->setNumber(100);
        $sample->setDescription('Sample with only SU');

        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Should succeed
        $this->assertNotNull($sample->getId());
        $this->assertNotNull($sample->getStratigraphicUnit());
        $this->assertNull($sample->getContext());
    }

    public function testTriggerAllowsSampleWithOnlyContext(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS2');
        $site->setDescription('Test site for trigger testing');
        $this->entityManager->persist($site);

        // Create a context
        $context = new Context();
        $context->setSite($site);
        $context->setType(0);
        $context->setName('Test Context');
        $context->setDescription('Test Context description');
        $this->entityManager->persist($context);

        $this->entityManager->flush();

        // Create sample with only context
        $sample = new Sample();
        $sample->setContext($context);
        $sample->setYear(2024);
        $sample->setNumber(100);
        $sample->setDescription('Sample with only context');

        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Should succeed
        $this->assertNotNull($sample->getId());
        $this->assertNull($sample->getStratigraphicUnit());
        $this->assertNotNull($sample->getContext());
    }

    public function testTriggerPreventsSampleWithBothReferences(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS3');
        $site->setDescription('Test site for trigger testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2024);
        $su->setNumber(1);
        $su->setDescription('Test SU');
        $this->entityManager->persist($su);

        // Create a context
        $context = new Context();
        $context->setSite($site);
        $context->setType(0);
        $context->setName('Test Context');
        $context->setDescription('Test Context description');
        $this->entityManager->persist($context);

        $this->entityManager->flush();

        // Try to create sample with both references - this should fail
        $sample = new Sample();
        $sample->setStratigraphicUnit($su);
        $sample->setContext($context);
        $sample->setYear(2024);
        $sample->setNumber(100);
        $sample->setDescription('Sample with both references - should fail');

        $this->entityManager->persist($sample);

        // Expect database exception due to trigger
        $this->expectException(Exception::class);
        $this->entityManager->flush();
    }

    public function testTriggerAllowsValidReferenceSwitch(): void
    {
        // Create a test site
        $site = new Site();
        $site->setName('Test Site');
        $site->setCode('TS5');
        $site->setDescription('Test site for trigger testing');
        $this->entityManager->persist($site);

        // Create a stratigraphic unit
        $su = new StratigraphicUnit();
        $su->setSite($site);
        $su->setYear(2024);
        $su->setNumber(1);
        $su->setDescription('Test SU');
        $this->entityManager->persist($su);

        // Create a context
        $context = new Context();
        $context->setSite($site);
        $context->setType(0);
        $context->setName('Test Context');
        $context->setDescription('Test Context description');
        $this->entityManager->persist($context);

        $this->entityManager->flush();

        // Create sample with stratigraphic unit
        $sample = new Sample();
        $sample->setStratigraphicUnit($su);
        $sample->setYear(2024);
        $sample->setNumber(100);
        $sample->setDescription('Sample for reference switch test');

        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Switch to context by removing stratigraphic unit first
        $sample->setContext($context);

        // This should succeed
        $this->entityManager->flush();

        $this->assertNull($sample->getStratigraphicUnit());
        $this->assertNotNull($sample->getContext());
    }
}
