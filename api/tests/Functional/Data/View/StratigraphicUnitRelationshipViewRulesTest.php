<?php

namespace App\Tests\Functional\Data\View;

use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\View\StratigraphicUnitRelationshipView;
use App\Entity\Vocabulary\StratigraphicUnit\Relationship;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StratigraphicUnitRelationshipViewRulesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testInsertRuleThroughORM(): void
    {
        // Get the first site from the database (as mentioned in requirements)
        $site = $this->entityManager->getRepository(Site::class)->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($site, 'No site found in database');

        // Get a relationship from vocabulary
        $relationship = $this->entityManager->getRepository(Relationship::class)->findOneBy([]);
        $this->assertNotNull($relationship, 'No relationship found in vocabulary');

        // Create two stratigraphic units for the first site
        $su1 = new StratigraphicUnit();
        $su1->setSite($site);
        $su1->setYear(2025);
        $su1->setNumber(1001);
        $su1->setDescription('Test SU 1 for ORM insert rule testing');
        $su1->setInterpretation('Test interpretation 1');

        $su2 = new StratigraphicUnit();
        $su2->setSite($site);
        $su2->setYear(2025);
        $su2->setNumber(1002);
        $su2->setDescription('Test SU 2 for ORM insert rule testing');
        $su2->setInterpretation('Test interpretation 2');

        $this->entityManager->persist($su1);
        $this->entityManager->persist($su2);
        $this->entityManager->flush();

        // Count relationships before insert
        $countBefore = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM stratigraphic_units_relationships')
            ->fetchOne();

        // Create and persist the view entity - this should trigger the insert rule
        $viewRelationship = new StratigraphicUnitRelationshipView();
        $viewRelationship->setLftStratigraphicUnit($su1);
        $viewRelationship->setRgtStratigraphicUnit($su2);
        $viewRelationship->setRelationship($relationship);

        $this->entityManager->persist($viewRelationship);
        $this->entityManager->flush();

        // Count relationships after insert
        $countAfter = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM stratigraphic_units_relationships')
            ->fetchOne();

        // Verify the insert rule worked - record was inserted into base table
        $this->assertEquals($countBefore + 1, $countAfter, 'Insert rule should add one record to base table via ORM');

        // Verify the view entity got an ID (indicating successful persistence)
        $this->assertNotNull($viewRelationship->getId(), 'View entity should have an ID after persistence');

        // Verify the record exists in the base table
        $result = $this->entityManager->getConnection()
            ->executeQuery(
                'SELECT * FROM stratigraphic_units_relationships WHERE lft_su_id = ? AND rgt_su_id = ? AND relationship_id = ?',
                [$su1->getId(), $su2->getId(), $relationship->getId()]
            )
            ->fetchAssociative();

        $this->assertNotEmpty($result, 'Record should exist in base table after ORM insert');
    }

    public function testDeleteRuleThroughORM(): void
    {
        // Get the first site from the database
        $site = $this->entityManager->getRepository(Site::class)->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($site, 'No site found in database');

        // Get a relationship from vocabulary
        $relationship = $this->entityManager->getRepository(Relationship::class)->findOneBy([]);
        $this->assertNotNull($relationship, 'No relationship found in vocabulary');

        // Create two stratigraphic units
        $su1 = new StratigraphicUnit();
        $su1->setSite($site);
        $su1->setYear(2025);
        $su1->setNumber(2001);
        $su1->setDescription('Test SU 1 for ORM delete rule testing');
        $su1->setInterpretation('Test interpretation 1');

        $su2 = new StratigraphicUnit();
        $su2->setSite($site);
        $su2->setYear(2025);
        $su2->setNumber(2002);
        $su2->setDescription('Test SU 2 for ORM delete rule testing');
        $su2->setInterpretation('Test interpretation 2');

        $this->entityManager->persist($su1);
        $this->entityManager->persist($su2);
        $this->entityManager->flush();

        // Create and persist a relationship through the view
        $viewRelationship = new StratigraphicUnitRelationshipView();
        $viewRelationship->setLftStratigraphicUnit($su1);
        $viewRelationship->setRgtStratigraphicUnit($su2);
        $viewRelationship->setRelationship($relationship);

        $this->entityManager->persist($viewRelationship);
        $this->entityManager->flush();

        // Store the ID for verification
        $relationshipId = $viewRelationship->getId();
        $this->assertNotNull($relationshipId, 'View relationship should have an ID');

        // Count relationships before delete
        $countBefore = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM stratigraphic_units_relationships')
            ->fetchOne();

        // Delete through the ORM - this should trigger the delete rule
        $this->entityManager->remove($viewRelationship);
        $this->entityManager->flush();

        // Count relationships after delete
        $countAfter = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM stratigraphic_units_relationships')
            ->fetchOne();

        // Verify the delete rule worked
        $this->assertEquals(
            $countBefore - 1,
            $countAfter,
            'The delete rule should remove one record from base table via ORM'
        );

        // Verify the record no longer exists in the base table
        $result = $this->entityManager->getConnection()
            ->executeQuery(
                'SELECT * FROM stratigraphic_units_relationships WHERE id = ?',
                [abs($relationshipId)] // Use abs() since view might have negative IDs
            )
            ->fetchAssociative();

        $this->assertEmpty($result, 'Record should be deleted from base table after ORM delete');
    }

    public function testViewShowsBidirectionalRelationships(): void
    {
        // Get the first site from the database
        $site = $this->entityManager->getRepository(Site::class)->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($site, 'No site found in database');

        // Get a relationship that has an inverse
        $relationship = $this->entityManager->getRepository(Relationship::class)
            ->createQueryBuilder('r')
            ->where('r.invertedBy != r.id')
            ->getQuery()
            ->getResult();

        if (empty($relationship)) {
            $this->markTestSkipped('No relationship with inverse found in vocabulary');
        }
        $relationship = $relationship[0];

        // Create two stratigraphic units
        $su1 = new StratigraphicUnit();
        $su1->setSite($site);
        $su1->setYear(2025);
        $su1->setNumber(3001);
        $su1->setDescription('Test SU 1 for bidirectional view testing');
        $su1->setInterpretation('Test interpretation 1');

        $su2 = new StratigraphicUnit();
        $su2->setSite($site);
        $su2->setYear(2025);
        $su2->setNumber(3002);
        $su2->setDescription('Test SU 2 for bidirectional view testing');
        $su2->setInterpretation('Test interpretation 2');

        $this->entityManager->persist($su1);
        $this->entityManager->persist($su2);
        $this->entityManager->flush();

        $viewRelationship = new StratigraphicUnitRelationshipView();
        $viewRelationship->setLftStratigraphicUnit($su1);
        $viewRelationship->setRgtStratigraphicUnit($su2);
        $viewRelationship->setRelationship($relationship);

        $this->entityManager->persist($viewRelationship);
        $this->entityManager->flush();

        // Query the view - should show both directions
        $viewResults = $this->entityManager->getConnection()
            ->executeQuery(
                'SELECT * FROM vw_stratigraphic_units_relationships WHERE (lft_su_id = ? AND rgt_su_id = ?) OR (lft_su_id = ? AND rgt_su_id = ?)',
                [$su1->getId(), $su2->getId(), $su2->getId(), $su1->getId()]
            )
            ->fetchAllAssociative();

        // Should find both the original and inverted relationship
        $this->assertCount(2, $viewResults, 'View should show bidirectional relationships');

        // One should have positive ID (original), one negative (inverted)
        $ids = array_column($viewResults, 'id');
        $hasPositive = false;
        $hasNegative = false;

        foreach ($ids as $id) {
            if ($id > 0) {
                $hasPositive = true;
            }
            if ($id < 0) {
                $hasNegative = true;
            }
        }

        $this->assertTrue($hasPositive, 'View should contain original relationship with positive ID');
        $this->assertTrue($hasNegative, 'View should contain inverted relationship with negative ID');
    }
}

