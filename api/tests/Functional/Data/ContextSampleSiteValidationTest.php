<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Context;
use App\Entity\Data\Join\ContextSample;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Vocabulary\Context\Type as ContextType;
use App\Entity\Vocabulary\Sample\Type as SampleType;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContextSampleSiteValidationTest extends KernelTestCase
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
        /** @var ContextType $contextType */
        $contextType = $this->getVocabulary(ContextType::class, ['group' => 'archaeology', 'value' => 'fill']);
        $context = new Context();
        $context->setSite($site1);
        $context->setType($contextType);
        $context->setName('test context');
        $context->setDescription('Test context description');

        // Create sample for site2 (different site)
        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample = new Sample();
        $sample->setSite($site2);
        $sample->setType($sampleType);
        $sample->setYear(2025);
        $sample->setNumber(999);
        $sample->setDescription('Test sample');

        $this->entityManager->persist($context);
        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Create ContextSample with different sites - should trigger exception
        $contextSample = new ContextSample();
        $contextSample->setContext($context);
        $contextSample->setSample($sample);

        $this->entityManager->persist($contextSample);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Context and sample must belong to the same site');

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

        // Create context and sample for same site initially
        /** @var ContextType $contextType */
        $contextType = $this->getVocabulary(ContextType::class, ['group' => 'archaeology', 'value' => 'fill']);
        $context1 = new Context();
        $context1->setSite($site1);
        $context1->setType($contextType);
        $context1->setName('test context 1');
        $context1->setDescription('Test context 1 description');

        $context2 = new Context();
        $context2->setSite($site2);
        $context2->setType($contextType);
        $context2->setName('test context 2');
        $context2->setDescription('Test context 2 description');

        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample = new Sample();
        $sample->setSite($site1);
        $sample->setType($sampleType);
        $sample->setYear(2025);
        $sample->setNumber(998);
        $sample->setDescription('Test sample');

        $this->entityManager->persist($context1);
        $this->entityManager->persist($context2);
        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Create valid ContextSample initially
        $contextSample = new ContextSample();
        $contextSample->setContext($context1);
        $contextSample->setSample($sample);

        $this->entityManager->persist($contextSample);
        $this->entityManager->flush();

        // Update to use context from different site - should trigger exception
        $contextSample->setContext($context2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Context and sample must belong to the same site');

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

        // Create context and sample for same site
        /** @var ContextType $contextType */
        $contextType = $this->getVocabulary(ContextType::class, ['group' => 'archaeology', 'value' => 'fill']);
        $context = new Context();
        $context->setSite($site);
        $context->setType($contextType);
        $context->setName('test context');
        $context->setDescription('Test context description');

        /** @var SampleType $sampleType */
        $sampleType = $this->getVocabulary(SampleType::class, ['code' => 'CO']);
        $sample = new Sample();
        $sample->setSite($site);
        $sample->setType($sampleType);
        $sample->setYear(2025);
        $sample->setNumber(997);
        $sample->setDescription('Test sample');

        $this->entityManager->persist($context);
        $this->entityManager->persist($sample);
        $this->entityManager->flush();

        // Create ContextSample with same site - should succeed
        $contextSample = new ContextSample();
        $contextSample->setContext($context);
        $contextSample->setSample($sample);

        $this->entityManager->persist($contextSample);
        $this->entityManager->flush();

        $this->assertNotNull($contextSample->getId());
        $this->assertEquals($context->getId(), $contextSample->getContext()->getId());
        $this->assertEquals($sample->getId(), $contextSample->getSample()->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
