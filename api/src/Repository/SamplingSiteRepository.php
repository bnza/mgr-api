<?php

namespace App\Repository;

use App\Entity\Data\SamplingSite;
use App\Entity\Data\SedimentCore;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SamplingSiteRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamplingSite::class);
    }

    /**
     * Returns the list of entity classes that still reference the given sampling site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof SamplingSite) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', SamplingSite::class, is_object($subject) ? get_debug_type($subject) : get_debug_type($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, SedimentCore::class, 'site')) {
            $result[] = SedimentCore::class;
        }

        return $result;
    }
}
