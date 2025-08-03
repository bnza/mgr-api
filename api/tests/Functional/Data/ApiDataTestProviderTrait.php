<?php

namespace App\Tests\Functional\Data;

use Doctrine\ORM\EntityManagerInterface;

trait ApiDataTestProviderTrait
{
    private EntityManagerInterface $entityManager;

    private function getVocabulary(string $className, array $criteria): object|string|null
    {
        return $this->entityManager
            ->getRepository($className)
            ->findOneBy($criteria);
    }
}
