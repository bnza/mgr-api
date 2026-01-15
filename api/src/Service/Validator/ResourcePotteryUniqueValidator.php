<?php

namespace App\Service\Validator;

use App\Entity\Data\Pottery;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\EntityManagerInterface;

class ResourcePotteryUniqueValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isUnique(array $criteria): bool
    {
        $this->support($criteria);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')
            ->from(Pottery::class, 'p')
            ->join('p.stratigraphicUnit', 'su')
            ->join(StratigraphicUnit::class, 'target_su', 'WITH', 'target_su.id = :su_id')
            ->where('p.inventory = :inventory')
            ->andWhere('su.site = target_su.site')
            ->setParameter('inventory', $criteria['inventory'])
            ->setParameter('su_id', $criteria['stratigraphicUnit']);

        $result = $qb->getQuery()->getOneOrNullResult();

        return null === $result;
    }

    /**
     * Checks if the given resource and criteria are supported.
     *
     * This method validates if the provided `$resource` exists in the predefined
     * unique fields and if the criteria match the unique fields for that resource.
     *
     * @param array $criteria the criteria to validate against the resource
     *
     * @return bool returns true if the resource and criteria match, otherwise an exception is thrown
     *
     * @throws \InvalidArgumentException if the resource does not exist or the criteria are not supported
     */
    public function support(array $criteria): bool
    {
        $criteriaKeys = array_keys($criteria);
        sort($criteriaKeys);

        $expectedKeys = ['inventory', 'stratigraphicUnit'];
        sort($expectedKeys);

        if ($criteriaKeys === $expectedKeys) {
            return true;
        }

        throw new \InvalidArgumentException(sprintf('Resource "%s" does not support criteria "%s".', Pottery::class, implode(', ', array_keys($criteria))));
    }
}
