<?php

namespace App\Repository;

use App\Entity\Data\ArchaeologicalSite;
use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\StratigraphicUnit;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchaeologicalSite::class);
    }

    /**
     * Returns the list of entity classes that still reference the given site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof ArchaeologicalSite) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', ArchaeologicalSite::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, StratigraphicUnit::class, 'site')) {
            $result[] = StratigraphicUnit::class;
        }

        if ($this->existsReference($subject, SedimentCore::class, 'site')) {
            $result[] = SedimentCore::class;
        }

        if ($this->existsReference($subject, Sample::class, 'site')) {
            $result[] = Sample::class;
        }

        if ($this->existsReference($subject, Context::class, 'site')) {
            $result[] = Context::class;
        }

        return $result;
    }
}
