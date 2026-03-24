<?php

namespace App\Repository;

use App\Entity\Data\PaleoclimateSample;
use App\Entity\Data\PaleoclimateSamplingSite;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaleoclimateSamplingSiteRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaleoclimateSamplingSite::class);
    }

    /**
     * Returns the list of entity classes that still reference the given sampling site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof PaleoclimateSamplingSite) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', PaleoclimateSamplingSite::class, is_object($subject) ? get_debug_type($subject) : get_debug_type($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, PaleoclimateSample::class, 'site')) {
            $result[] = PaleoclimateSample::class;
        }

        return $result;
    }
}
