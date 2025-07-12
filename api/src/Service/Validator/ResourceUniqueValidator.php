<?php

namespace App\Service\Validator;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Data\Site;
use App\Entity\Auth\User;
use Doctrine\ORM\EntityManagerInterface;

class ResourceUniqueValidator
{
    /**
     * Array of field combination tuples that define uniqueness constraints.
     *
     * Each element in the array represents a unique constraint composed of one or more fields.
     * Single-field constraints are represented as arrays with one string element.
     * Multi-field constraints are represented as arrays with multiple string elements.
     *
     * @example
     * // Single field uniqueness constraints
     * [
     *     ['email'],           // email field must be unique
     *     ['username'],        // username field must be unique
     * ]
     *
     * @example
     * // Multi-field uniqueness constraints
     * [
     *     ['tenant_id', 'email'],      // combination of tenant_id + email must be unique
     *     ['company_id', 'department', 'role'], // combination of company_id + department + role must be unique
     * ]
     *
     * @example
     * // Mixed single and multi-field constraints
     * [
     *     ['email'],                   // email must be unique globally
     *     ['phone_number'],            // phone_number must be unique globally
     *     ['organization_id', 'code'], // combination of organization_id + code must be unique
     * ]
     */
    private const array RESOURCE_UNIQUE_FIELDS = [
        Site::class => [['code'], ['name']],
        SiteUserPrivilege::class => [['site', 'user']],
        User::class => [['email']],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function isUnique(string $resource, array $criteria): bool
    {
        $this->support($resource, array_keys($criteria));

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')
            ->from($resource, 'r');
        foreach ($criteria as $field => $value) {
            $qb->andWhere('r.' . $field . ' = :' . $field);
            $qb->setParameter($field, $value);
        }
        $result = $qb->getQuery()->getOneOrNullResult();
        return $result === null; // Fixed: return true if no result found (unique)
    }

    /**
     * Checks if the given resource and criteria are supported.
     *
     * This method validates if the provided `$resource` exists in the predefined
     * unique fields and if the criteria match the unique fields for that resource.
     *
     * @param string $resource The resource to check for support.
     * @param array $criteria The criteria to validate against the resource.
     *
     * @return bool Returns true if the resource and criteria match, otherwise an exception is thrown.
     *
     * @throws \InvalidArgumentException If the resource does not exist or the criteria are not supported.
     */
    private function support(string $resource, array $criteria): bool
    {
        if (!array_key_exists($resource, self::RESOURCE_UNIQUE_FIELDS)) {
            throw new \InvalidArgumentException(sprintf('Resource "%s" is not supported.', $resource));
        }

        // Replace array_any with manual check
        foreach (self::RESOURCE_UNIQUE_FIELDS[$resource] as $uniqueField) {
            if (count(array_intersect($uniqueField, $criteria)) === count($criteria)) {
                return true;
            }
        }

        throw new \InvalidArgumentException(sprintf('Resource "%s" does not support criteria "%s".', $resource, implode(', ', $criteria)));
    }
}
