<?php

namespace App\Repository\Traits;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait to help repositories report which entity classes still reference a given subject.
 *
 * Repositories using this trait must implement the abstract method
 * `getReferencingEntityClasses(object $subject): array` to provide the
 * domain-specific list of classes to check. This trait offers a shared static
 * `$exists` helper to perform a minimal existence check in a consistent way.
 */
trait ReferencingEntityClassesTrait
{
    /**
     * Consumers are Doctrine repositories and must provide access to the EM.
     */
    abstract protected function getEntityManager(): EntityManagerInterface;

    /**
     * Must be implemented by each repository to return a list of entity class
     * names that reference the provided subject.
     *
     * @return array<class-string>
     */
    abstract public function getReferencingEntityClasses(object $subject): array;

    /**
     * Shared static helper to check if at least one row exists in the given
     * entity class where the provided relation field equals the given subject.
     */
    protected static function exists(EntityManagerInterface $em, object $subject, string $entityClass, string $field): bool
    {
        $qb = $em->createQueryBuilder();
        $qb->select('1')
            ->from($entityClass, 'x')
            ->where(sprintf('x.%s = :subject', $field))
            ->setParameter('subject', $subject)
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Convenience wrapper for the static exists method using this repository's
     * entity manager instance.
     */
    protected function existsReference(object $subject, string $entityClass, string $field): bool
    {
        return self::exists($this->getEntityManager(), $subject, $entityClass, $field);
    }
}
