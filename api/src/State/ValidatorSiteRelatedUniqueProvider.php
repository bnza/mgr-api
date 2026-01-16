<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Resource\Validator\UniqueValidator;
use App\Service\Validator\ResourceSiteRelatedUniqueValidator;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ValidatorSiteRelatedUniqueProvider implements ProviderInterface
{
    public function __construct(
        private ResourceSiteRelatedUniqueValidator $validator,
        private RequestStack                       $requestStack,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $defaults = $operation->getDefaults() ?? [];
        if (!isset($defaults['resource'])) {
            throw new \RuntimeException('Resource not found in operation defaults');
        }

        $criteria = $this->getQueryParameters();

        $unique = $this->validator->isUnique($defaults['resource'], $criteria);

        return new UniqueValidator($criteria, $unique);
    }

    /**
     * Gets query parameters from the current request.
     *
     * @return array The query parameters as key-value pairs
     */
    private function getQueryParameters(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return [];
        }

        return $request->query->all();
    }
}
