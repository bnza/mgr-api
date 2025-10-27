<?php

namespace App\Repository;

use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\MediaObject;
use App\Entity\Data\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
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
    public function getReferencingEntityClasses(User $user): array
    {
        $em = $this->getEntityManager();
        $result = [];

        $exists = static function (string $entityClass, string $field) use ($em, $user): bool {
            $qb = $em->createQueryBuilder();

            // EXISTS (SELECT x.id FROM <entity> x WHERE x.<field> = :user)
            $subDql = $em->createQueryBuilder()
                ->select('x.id')
                ->from($entityClass, 'x')
                ->where(sprintf('x.%s = :user', $field))
                ->setMaxResults(1)
                ->getDQL();

            $qb->select('1')
                ->from(User::class, 'u')
                ->where('u = :user')
                ->andWhere($qb->expr()->exists($subDql))
                ->setParameter('user', $user)
                ->setMaxResults(1);

            return null !== $qb->getQuery()->getOneOrNullResult();
        };

        if ($exists(Analysis::class, 'createdBy')) {
            $result[] = Analysis::class;
        }

        if ($exists(Site::class, 'createdBy')) {
            $result[] = Site::class;
        }

        if ($exists(MediaObject::class, 'uploadedBy')) {
            $result[] = MediaObject::class;
        }

        return $result;
    }
}
