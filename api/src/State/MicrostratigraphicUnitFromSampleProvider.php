<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Paginator as ApiPlatformOrmPaginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Data\MicrostratigraphicUnit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class MicrostratigraphicUnitFromSampleProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[AutowireIterator('api_platform.doctrine.orm.query_extension.collection')]
        private iterable $collectionExtensions,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $sampleId = $uriVariables['parentId'] ?? null;

        // Build the base query: o -> su -> sus -> s
        $rootAlias = 'o';
        $qb = $this->entityManager->getRepository(MicrostratigraphicUnit::class)->createQueryBuilder($rootAlias);

        $queryNameGenerator = new QueryNameGenerator();
        if ($sampleId) {
            $suAlias = $queryNameGenerator->generateJoinAlias('stratigraphic_unit');
            $susAlias = $queryNameGenerator->generateJoinAlias('stratigraphic_unit_samples');
            $sampleAlias = $queryNameGenerator->generateJoinAlias('samples');
            $qb
                ->join("$rootAlias.stratigraphicUnit", $suAlias)
                ->join("$suAlias.stratigraphicUnitSamples", $susAlias)
                ->join("$susAlias.sample", $sampleAlias)
                ->andWhere("$sampleAlias.id = :sampleId")
                ->setParameter('sampleId', $sampleId);
        } else {
            // empty result if no parent id
            $qb->andWhere('1 = 0');
        }

        $extensionsContext = $context;
        unset($extensionsContext['uri_variables']);

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($qb, $queryNameGenerator, MicrostratigraphicUnit::class, $operation, $extensionsContext);
        }

        // Wrap in Doctrine + API Platform paginator to get Hydra pagination metadata
        $doctrinePaginator = new DoctrinePaginator($qb, true);

        return new ApiPlatformOrmPaginator($doctrinePaginator);
    }
}
