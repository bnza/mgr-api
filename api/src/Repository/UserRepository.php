<?php

namespace App\Repository;

use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\ArchaeologicalSite;
use App\Entity\Data\MediaObject;
use App\Repository\Traits\ReferencingEntityClassesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use ReferencingEntityClassesTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        // Don't forget to persist the changes to the database
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns the list of entity classes that still reference the given user.
     * Uses DQL EXISTS subqueries to check for the presence of related rows.
     *
     * @return array<class-string>
     */
    public function getReferencingEntityClasses(object $subject): array
    {
        if (!$subject instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, %s given', User::class, is_object($subject) ? get_debug_type($subject) : gettype($subject)));
        }
        $result = [];

        if ($this->existsReference($subject, Analysis::class, 'createdBy')) {
            $result[] = Analysis::class;
        }

        if ($this->existsReference($subject, ArchaeologicalSite::class, 'createdBy')) {
            $result[] = ArchaeologicalSite::class;
        }

        if ($this->existsReference($subject, MediaObject::class, 'uploadedBy')) {
            $result[] = MediaObject::class;
        }

        return $result;
    }
}
