<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class SiteChildCollectionProvider implements ProviderInterface
{
    private const SUPPORTED_TEMPLATES = [
        '/data/archaeological_sites/{parentId}/botany/charcoals',
        '/data/archaeological_sites/{parentId}/botany/seeds',
        '/data/archaeological_sites/{parentId}/individuals',
        '/data/archaeological_sites/{parentId}/microstratigraphic_units',
        '/data/archaeological_sites/{parentId}/potteries',
        '/data/archaeological_sites/{parentId}/zoo/bones',
        '/data/archaeological_sites/{parentId}/zoo/teeth',
    ];

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (in_array($operation->getUriTemplate(), self::SUPPORTED_TEMPLATES, true)) {
            $context['uri_variables'] = $uriVariables;

            return $this->collectionProvider->provide(
                $operation->withUriVariables([]),
                [],
                $context
            );
        }

        return $this->collectionProvider->provide($operation, $uriVariables, $context);
    }
}
