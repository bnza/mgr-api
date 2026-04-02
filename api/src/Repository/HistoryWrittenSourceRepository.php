<?php

namespace App\Repository;

use App\Entity\Data\History\WrittenSource;
use App\Entity\Data\History\WrittenSourceCitedWork;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoryWrittenSourceRepository extends ServiceEntityRepository
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WrittenSource::class);
    }

    /**
     * Returns the list of entity classes that still reference the given site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof WrittenSource) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', WrittenSource::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, WrittenSourceCitedWork::class, 'writtenSource')) {
            $result[] = WrittenSourceCitedWork::class;
        }

        return $result;
    }
}
