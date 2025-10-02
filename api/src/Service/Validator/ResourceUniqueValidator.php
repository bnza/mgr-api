<?php

namespace App\Service\Validator;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\Context;
use App\Entity\Data\Individual;
use App\Entity\Data\Join\Analysis\AnalysisContextZoo;
use App\Entity\Data\Join\Analysis\AnalysisPottery;
use App\Entity\Data\Join\Analysis\AnalysisSampleMicrostratigraphicUnit;
use App\Entity\Data\Join\Analysis\AnalysisSiteAnthropology;
use App\Entity\Data\Join\Analysis\AnalysisZooBone;
use App\Entity\Data\Join\Analysis\AnalysisZooTooth;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\MediaObject\MediaObjectAnalysis;
use App\Entity\Data\Join\MediaObject\MediaObjectStratigraphicUnit;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Join\SedimentCoreDepth;
use App\Entity\Data\MediaObject;
use App\Entity\Data\MicrostratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\View\StratigraphicUnitRelationshipView;
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
     * @example
     * // Multi-field uniqueness constraints
     * [
     *     ['tenant_id', 'email'],      // combination of tenant_id + email must be unique
     *     ['company_id', 'department', 'role'], // combination of company_id + department + role must be unique
     * ]
     * @example
     * // Mixed single and multi-field constraints
     * [
     *     ['email'],                   // email must be unique globally
     *     ['phone_number'],            // phone_number must be unique globally
     *     ['organization_id', 'code'], // combination of organization_id + code must be unique
     * ]
     */
    private const array RESOURCE_UNIQUE_FIELDS = [
        Analysis::class => [['type', 'identifier']],
        AnalysisContextZoo::class => [['subject', 'analysis']],
        AnalysisPottery::class => [['subject', 'analysis']],
        AnalysisSampleMicrostratigraphicUnit::class => [['subject', 'analysis']],
        AnalysisSiteAnthropology::class => [['subject', 'analysis']],
        AnalysisZooBone::class => [['subject', 'analysis']],
        AnalysisZooTooth::class => [['subject', 'analysis']],
        Context::class => [['site', 'name']],
        Individual::class => [['identifier']],
        ContextStratigraphicUnit::class => [['context', 'stratigraphicUnit']],
        MicrostratigraphicUnit::class => [['stratigraphicUnit', 'identifier']],
        MediaObject::class => [['sha256']],
        MediaObjectAnalysis::class => [['mediaObject', 'item']],
        MediaObjectStratigraphicUnit::class => [['mediaObject', 'item']],
        Pottery::class => [['inventory']],
        Sample::class => [['site', 'type', 'year', 'number']],
        SampleStratigraphicUnit::class => [['sample', 'stratigraphicUnit']],
        SedimentCore::class => [['site', 'year', 'number']],
        SedimentCoreDepth::class => [['sedimentCore', 'depthMin']],
        Site::class => [['code'], ['name']],
        SiteUserPrivilege::class => [['site', 'user']],
        StratigraphicUnit::class => [['site', 'year', 'number']],
        StratigraphicUnitRelationshipView::class => [['lftStratigraphicUnit', 'rgtStratigraphicUnit']],
        User::class => [['email']],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isUnique(string $resource, array $criteria): bool
    {
        $this->support($resource, array_keys($criteria));

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')
            ->from($resource, 'r');
        foreach ($criteria as $field => $value) {
            $qb->andWhere('r.'.$field.' = :'.$field);
            $qb->setParameter($field, $value);
        }
        $result = $qb->getQuery()->getOneOrNullResult();

        return null === $result; // Fixed: return true if no result found (unique)
    }

    /**
     * Checks if the given resource and criteria are supported.
     *
     * This method validates if the provided `$resource` exists in the predefined
     * unique fields and if the criteria match the unique fields for that resource.
     *
     * @param string $resource the resource to check for support
     * @param array  $criteria the criteria to validate against the resource
     *
     * @return bool returns true if the resource and criteria match, otherwise an exception is thrown
     *
     * @throws \InvalidArgumentException if the resource does not exist or the criteria are not supported
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
