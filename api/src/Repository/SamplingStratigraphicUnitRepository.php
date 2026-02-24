<?php

namespace App\Repository;

use App\Entity\Data\SamplingStratigraphicUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SamplingStratigraphicUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamplingStratigraphicUnit::class);
    }
}
