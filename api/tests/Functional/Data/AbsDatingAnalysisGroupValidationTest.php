<?php

namespace App\Tests\Functional\Data;

use App\Entity\Data\Analysis;
use App\Entity\Data\Botany\Charcoal;
use App\Entity\Data\Botany\Seed;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisBotanySeed;
use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Entity\Vocabulary\Analysis\Type as AnalysisType;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AbsDatingAnalysisGroupValidationTest extends KernelTestCase
{
    use ApiDataTestProviderTrait;

    private EntityManagerInterface $entityManager;

    // Vocabulary\Analysis\Type IDs from fixtures
    private const int TYPE_ID_C14 = 101;      // group: absolute dating
    private const int TYPE_ID_THL = 102;      // group: absolute dating (thermoluminescence)
    private const int TYPE_ID_ANTHRA = 201;   // group: assemblage (non-absolute)

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testCharcoalInsertChildWithNonAbsoluteGroupThrowsException(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        $analysis = new Analysis()
            ->setIdentifier('T-CHAR-NONABS-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanyCharcoal()
            ->setAnalysis($join)
            ->setDatingLower(700)
            ->setDatingUpper(900)
            ->setUncalibratedDating(810)
            ->setError(35)
            ->setCalibrationCurve('IntCal20');

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);

        $this->expectException(DBALException::class);
        // Error text from migration: '<abs_table>.id "%" must reference an analysis with group = 'absolute dating' (found %)'
        $this->expectExceptionMessageMatches('/abs_dating_analysis_botany_charcoals\.id\s+"?\d+"?\s+must reference an analysis with group = \'absolute dating\' \(found .+\)/i');

        $this->entityManager->flush();
    }

    public function testCharcoalValidInsertWithAbsoluteGroupSucceeds(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        $analysis = new Analysis()
            ->setIdentifier('T-CHAR-ABS-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanyCharcoal()
            ->setAnalysis($join)
            ->setDatingLower(500)
            ->setDatingUpper(1200)
            ->setUncalibratedDating(980)
            ->setError(30)
            ->setCalibrationCurve('IntCal20')
            ->setNotes('Charcoal C14 measurement using IntCal20.');

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        self::assertNotNull($join->getId());
        self::assertSame($join->getId(), $child->getId());
    }

    public function testCharcoalUpdateParentGroupToNonAbsoluteThrowsException(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        // Create with absolute dating type first
        $analysis = new Analysis()
            ->setIdentifier('T-CHAR-UPD-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_THL));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanyCharcoal()
            ->setAnalysis($join)
            ->setDatingLower(600)
            ->setDatingUpper(1100)
            ->setUncalibratedDating(910)
            ->setError(40)
            ->setCalibrationCurve('LatestTL')
            ->setNotes('Thermoluminescence with latest curve.');

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        // Now try to change the analysis type to a non-absolute group while child exists
        $analysis->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $this->expectException(DBALException::class);
        // Error text from migration: 'Cannot set analysis % to group % while an abs_dating child row exists'
        $this->expectExceptionMessageMatches('/Cannot set analysis\s+\d+\s+to group\s+.+\s+while an abs_dating child row exists/i');

        $this->entityManager->flush();
    }

    public function testSeedInsertChildWithNonAbsoluteGroupThrowsException(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        $analysis = new Analysis()
            ->setIdentifier('T-SEED-NONABS-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanySeed()
            ->setAnalysis($join)
            ->setDatingLower(750)
            ->setDatingUpper(1050)
            ->setUncalibratedDating(890)
            ->setError(28)
            ->setCalibrationCurve('IntCal20');

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);

        $this->expectException(DBALException::class);
        $this->expectExceptionMessageMatches('/abs_dating_analysis_botany_seeds\.id\s+"?\d+"?\s+must reference an analysis with group = \'absolute dating\' \(found .+\)/i');

        $this->entityManager->flush();
    }

    public function testSeedValidInsertWithAbsoluteGroupSucceeds(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        $analysis = new Analysis()
            ->setIdentifier('T-SEED-ABS-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanySeed()
            ->setAnalysis($join)
            ->setDatingLower(540)
            ->setDatingUpper(1180)
            ->setUncalibratedDating(1005)
            ->setError(32)
            ->setCalibrationCurve('IntCal20'); // no notes to keep ~2/3 coverage overall

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        self::assertNotNull($join->getId());
        self::assertSame($join->getId(), $child->getId());
    }

    public function testSeedUpdateParentGroupToNonAbsoluteThrowsException(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        $analysis = new Analysis()
            ->setIdentifier('T-SEED-UPD-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_THL));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis);

        $child = new AbsDatingAnalysisBotanySeed()
            ->setAnalysis($join)
            ->setDatingLower(680)
            ->setDatingUpper(1120)
            ->setUncalibratedDating(940)
            ->setError(27)
            ->setCalibrationCurve('LatestTL')
            ->setNotes('TL dating run with updated protocol.');

        $this->entityManager->persist($analysis);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        // Attempt invalid change
        $analysis->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $this->expectException(DBALException::class);
        $this->expectExceptionMessageMatches('/Cannot set analysis\s+\d+\s+to group\s+.+\s+while an abs_dating child row exists/i');

        $this->entityManager->flush();
    }

    public function testCharcoalUpdateAnalysisIdWithAbsoluteDatingAndChildThrowsException(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        // Create first analysis with absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-CHAR-AID-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis1);

        $child = new AbsDatingAnalysisBotanyCharcoal()
            ->setAnalysis($join)
            ->setDatingLower(700)
            ->setDatingUpper(950)
            ->setUncalibratedDating(825)
            ->setError(30)
            ->setCalibrationCurve('IntCal20');

        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        // Create second analysis to attempt switching to
        $analysis2 = new Analysis()
            ->setIdentifier('T-CHAR-AID-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Try to update analysis_id when old analysis is absolute dating and has child
        $join->setAnalysis($analysis2);

        $this->expectException(DBALException::class);
        // Error text from migration: 'Cannot update analysis_id in {table} (id=%) because it is linked to absolute dating analysis (%) and has a related {abs_table} entry'
        $this->expectExceptionMessageMatches('/Cannot update analysis_id in analysis_botany_charcoals .+because it is linked to absolute dating analysis.+and has a related abs_dating_analysis_botany_charcoals entry/i');

        $this->entityManager->flush();
    }

    public function testCharcoalUpdateAnalysisIdWithoutAbsoluteDatingChildSucceeds(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        // Create first analysis with absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-CHAR-AIDOK-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis1);

        // No abs_dating child created
        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->flush();

        // Create second analysis
        $analysis2 = new Analysis()
            ->setIdentifier('T-CHAR-AIDOK-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_THL));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Update analysis_id should succeed since no abs_dating child exists
        $join->setAnalysis($analysis2);
        $this->entityManager->flush();

        self::assertSame($analysis2->getId(), $join->getAnalysis()->getId());
    }

    public function testCharcoalUpdateAnalysisIdWithNonAbsoluteDatingSucceeds(): void
    {
        $charcoal = $this->entityManager->getRepository(Charcoal::class)->findOneBy([]);
        self::assertNotNull($charcoal, 'Expected a Charcoal subject from test fixtures.');

        // Create first analysis with NON-absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-CHAR-AIDNON-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $join = new AnalysisBotanyCharcoal();
        $join->setSubject($charcoal);
        $join->setAnalysis($analysis1);

        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->flush();

        // Create second analysis
        $analysis2 = new Analysis()
            ->setIdentifier('T-CHAR-AIDNON-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Update analysis_id should succeed since old analysis is not absolute dating
        $join->setAnalysis($analysis2);
        $this->entityManager->flush();

        self::assertSame($analysis2->getId(), $join->getAnalysis()->getId());
    }

    public function testSeedUpdateAnalysisIdWithAbsoluteDatingAndChildThrowsException(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        // Create first analysis with absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-SEED-AID-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_THL));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis1);

        $child = new AbsDatingAnalysisBotanySeed()
            ->setAnalysis($join)
            ->setDatingLower(650)
            ->setDatingUpper(1000)
            ->setUncalibratedDating(800)
            ->setError(35)
            ->setCalibrationCurve('LatestTL');

        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        // Create second analysis
        $analysis2 = new Analysis()
            ->setIdentifier('T-SEED-AID-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Try to update analysis_id when old analysis is absolute dating and has child
        $join->setAnalysis($analysis2);

        $this->expectException(DBALException::class);
        $this->expectExceptionMessageMatches('/Cannot update analysis_id in analysis_botany_seeds .+because it is linked to absolute dating analysis.+and has a related abs_dating_analysis_botany_seeds entry/i');

        $this->entityManager->flush();
    }

    public function testSeedUpdateAnalysisIdWithoutAbsoluteDatingChildSucceeds(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        // Create first analysis with absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-SEED-AIDOK-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_C14));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis1);

        // No abs_dating child created
        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->flush();

        // Create second analysis
        $analysis2 = new Analysis()
            ->setIdentifier('T-SEED-AIDOK-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_THL));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Update analysis_id should succeed since no abs_dating child exists
        $join->setAnalysis($analysis2);
        $this->entityManager->flush();

        self::assertSame($analysis2->getId(), $join->getAnalysis()->getId());
    }

    public function testSeedUpdateAnalysisIdWithNonAbsoluteDatingSucceeds(): void
    {
        $seed = $this->entityManager->getRepository(Seed::class)->findOneBy([]);
        self::assertNotNull($seed, 'Expected a Seed subject from test fixtures.');

        // Create first analysis with NON-absolute dating type
        $analysis1 = new Analysis()
            ->setIdentifier('T-SEED-AIDNON-1-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $join = new AnalysisBotanySeed();
        $join->setSubject($seed);
        $join->setAnalysis($analysis1);

        $this->entityManager->persist($analysis1);
        $this->entityManager->persist($join);
        $this->entityManager->flush();

        // Create second analysis
        $analysis2 = new Analysis()
            ->setIdentifier('T-SEED-AIDNON-2-'.uniqid())
            ->setYear(2025)
            ->setType($this->entityManager->getReference(AnalysisType::class, self::TYPE_ID_ANTHRA));

        $this->entityManager->persist($analysis2);
        $this->entityManager->flush();

        // Update analysis_id should succeed since old analysis is not absolute dating
        $join->setAnalysis($analysis2);
        $this->entityManager->flush();

        self::assertSame($analysis2->getId(), $join->getAnalysis()->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
