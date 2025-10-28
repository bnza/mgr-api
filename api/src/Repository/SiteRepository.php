<?php

namespace App\Repository;

use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    /**
     * Returns the list of entity classes that still reference the given site.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(Site $site): array
    {
        $em = $this->getEntityManager();
        $result = [];

        $exists = static function (string $entityClass, string $field) use ($em, $site): bool {
            $qb = $em->createQueryBuilder();

            // EXISTS (SELECT x.id FROM <entity> x WHERE x.<field> = :site)
            $subDql = $em->createQueryBuilder()
                ->select('x.id')
                ->from($entityClass, 'x')
                ->where(sprintf('x.%s = :site', $field))
                ->setMaxResults(1)
                ->getDQL();

            $qb->select('1')
                ->from(Site::class, 's')
                ->where('s = :site')
                ->andWhere($qb->expr()->exists($subDql))
                ->setParameter('site', $site)
                ->setMaxResults(1);

            return null !== $qb->getQuery()->getOneOrNullResult();
        };

        if ($exists(StratigraphicUnit::class, 'site')) {
            $result[] = StratigraphicUnit::class;
        }

        if ($exists(SedimentCore::class, 'site')) {
            $result[] = SedimentCore::class;
        }

        if ($exists(Sample::class, 'site')) {
            $result[] = Sample::class;
        }

        if ($exists(Context::class, 'site')) {
            $result[] = Context::class;
        }

        return $result;
    }
}
