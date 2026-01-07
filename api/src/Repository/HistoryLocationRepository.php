<?php

namespace App\Repository;

use App\Entity\Data\History\Animal;
use App\Entity\Data\History\Plant;
use App\Entity\Vocabulary\History\Location;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoryLocationRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Returns the list of entity classes that still reference the given site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof Location) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', Location::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, Animal::class, 'location')) {
            $result[] = Animal::class;
        }

        if ($this->existsReference($subject, Plant::class, 'location')) {
            $result[] = Plant::class;
        }

        return $result;
    }
}
