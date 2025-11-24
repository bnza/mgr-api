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
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StratigraphicUnitRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

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
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof StratigraphicUnit) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', StratigraphicUnit::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, MicrostratigraphicUnit::class, 'stratigraphicUnit')) {
            $result[] = MicrostratigraphicUnit::class;
        }

        if ($this->existsReference($subject, Pottery::class, 'stratigraphicUnit')) {
            $result[] = Pottery::class;
        }

        if ($this->existsReference($subject, Individual::class, 'stratigraphicUnit')) {
            $result[] = Individual::class;
        }

        if ($this->existsReference($subject, Tooth::class, 'stratigraphicUnit')) {
            $result[] = Tooth::class;
        }

        if ($this->existsReference($subject, Bone::class, 'stratigraphicUnit')) {
            $result[] = Bone::class;
        }

        if ($this->existsReference($subject, Seed::class, 'stratigraphicUnit')) {
            $result[] = Seed::class;
        }

        if ($this->existsReference($subject, Charcoal::class, 'stratigraphicUnit')) {
            $result[] = Charcoal::class;
        }

        return $result;
    }
}
