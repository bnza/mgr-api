<?php

namespace App\Repository;

use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\ArchaeologicalSite;
use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AnalysisRepository extends ServiceEntityRepository
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
        if (!$subject instanceof Analysis) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', ArchaeologicalSite::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, AnalysisBotanyCharcoal::class, 'analysis')) {
            $result[] = AnalysisBotanyCharcoal::class;
        }

        if ($this->existsReference($subject, AnalysisBotanySeed::class, 'analysis')) {
            $result[] = AnalysisBotanySeed::class;
        }

        return $result;
    }

    public function userHasAnalysis(?User $user): bool
    {
        return $user && $this->createQueryBuilder('o')
                ->select('1')
                ->where('o.createdBy = :user')
                ->setParameter('user', $user->getId())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    }
}
