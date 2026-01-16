<?php

namespace App\Service\Validator;

use App\Entity\Data\Individual;
use App\Entity\Data\Pottery;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\EntityManagerInterface;

class ResourceSiteRelatedUniqueValidator
{
    public const array SUPPORTED_RESOURCES = [
        Pottery::class => 'inventory',
        Individual::class => 'identifier',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function isUnique(string $resource, array $criteria): bool
    {
        $this->support($resource, $criteria);

        $identifierField = self::SUPPORTED_RESOURCES[$resource];

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')
            ->from($resource, 'r')
            ->join('r.stratigraphicUnit', 'su')
            ->join(StratigraphicUnit::class, 'target_su', 'WITH', 'target_su.id = :su_id')
            ->where('r.' . $identifierField . ' = :identifier')
            ->andWhere('su.site = target_su.site')
            ->setParameter('identifier', $criteria[$identifierField])
            ->setParameter('su_id', $criteria['stratigraphicUnit']);

        if (isset($criteria['id'])) {
            $qb->andWhere('r.id != :id')
                ->setParameter('id', $criteria['id']);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return null === $result;
    }

    /**
     * Checks if the given resource and criteria are supported.
     *
     * This method validates if the provided `$resource` exists in the predefined
     * unique fields and if the criteria match the unique fields for that resource.
     *
     * @param string $resource the resource to check for support
     * @param array $criteria the criteria to validate against the resource
     *
     * @return bool returns true if the resource and criteria match, otherwise an exception is thrown
     *
     * @throws \InvalidArgumentException if the resource does not exist or the criteria are not supported
     */
    public function support(string $resource, array $criteria): bool
    {
        if (!array_key_exists($resource, self::SUPPORTED_RESOURCES)) {
            throw new \InvalidArgumentException(sprintf('Resource "%s" is not supported by %s.', $resource, self::class));
        }

        $identifierField = self::SUPPORTED_RESOURCES[$resource];
        $criteriaKeys = array_keys($criteria);

        $expectedKeys = [$identifierField, 'stratigraphicUnit'];
        if (isset($criteria['id'])) {
            $expectedKeys[] = 'id';
        }

        sort($criteriaKeys);
        sort($expectedKeys);

        if ($criteriaKeys === $expectedKeys) {
            return true;
        }

        throw new \InvalidArgumentException(sprintf('Resource "%s" does not support criteria "%s".', $resource, implode(', ', array_keys($criteria))));
    }
}
