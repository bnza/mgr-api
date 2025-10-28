<?php

namespace App\Repository;

use App\Entity\Data\Botany\Charcoal;
use App\Entity\Data\Botany\Seed;
use App\Entity\Data\Individual;
use App\Entity\Data\MicrostratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\Zoo\Bone;
use App\Entity\Data\Zoo\Tooth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StratigraphicUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StratigraphicUnit::class);
    }

    /**
     * Returns the list of entity classes that still reference the given stratigraphic unit.
     * It checks a minimal existence of rows referencing the provided unit via a simple EXISTS-like query.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(StratigraphicUnit $stratigraphicUnit): array
    {
        $em = $this->getEntityManager();
        $result = [];

        $exists = static function (string $entityClass, string $field) use ($em, $stratigraphicUnit): bool {
            // SELECT 1 FROM <entity> x WHERE x.<field> = :su LIMIT 1
            $qb = $em->createQueryBuilder();
            $qb->select('1')
                ->from($entityClass, 'x')
                ->where(sprintf('x.%s = :su', $field))
                ->setParameter('su', $stratigraphicUnit)
                ->setMaxResults(1);

            return null !== $qb->getQuery()->getOneOrNullResult();
        };

        if ($exists(MicrostratigraphicUnit::class, 'stratigraphicUnit')) {
            $result[] = MicrostratigraphicUnit::class;
        }

        if ($exists(Pottery::class, 'stratigraphicUnit')) {
            $result[] = Pottery::class;
        }

        if ($exists(Individual::class, 'stratigraphicUnit')) {
            $result[] = Individual::class;
        }

        if ($exists(Tooth::class, 'stratigraphicUnit')) {
            $result[] = Tooth::class;
        }

        if ($exists(Bone::class, 'stratigraphicUnit')) {
            $result[] = Bone::class;
        }

        if ($exists(Seed::class, 'stratigraphicUnit')) {
            $result[] = Seed::class;
        }

        if ($exists(Charcoal::class, 'stratigraphicUnit')) {
            $result[] = Charcoal::class;
        }

        return $result;
    }
}
