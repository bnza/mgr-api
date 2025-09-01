<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Resource\Validator\UniqueValidator;
use App\Service\Validator\ResourceUniqueValidator;
use Symfony\Component\HttpKernel\Exception\HttpException;

readonly class ValidatorUniqueProvider implements ProviderInterface
{
    public function __construct(private ResourceUniqueValidator $validator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $defaults = $operation->getDefaults();
        if (
            !is_array($defaults)
            && !array_key_exists('resource', $defaults)
        ) {
            throw new HttpException(404, 'Not found');
        }

        $criteria = $this->mapUriVariables($operation, $uriVariables);

        $unique = $this->validator->isUnique($defaults['resource'], $criteria);

        return new UniqueValidator($criteria, $unique);
    }

    /**
     * Maps URI variables to correct parameter names based on operation requirements.
     *
     * This is a temporary fix for an API Platform issue where a single last template variable
     * in the uriTemplate is always mapped as 'id' regardless of the actual template variable
     * name or the requirements parameter configuration.
     *
     * For example, with uriTemplate '/validator/unique/site/code/{code}' and requirements
     * ['code' => '[a-zA-Z0-9]+'], the URI variable is incorrectly mapped as ['id' => 'value']
     * instead of ['code' => 'value'].
     *
     * This method corrects the mapping by using the first key from the operation's requirements
     * as the proper parameter name when dealing with a single URI variable named 'id'.
     *
     * @param Operation $operation    The API Platform operation containing requirements
     * @param array     $uriVariables The URI variables array from the request
     *
     * @return array The corrected URI variables array with proper parameter names
     */
    private function mapUriVariables(Operation $operation, array $uriVariables): array
    {
        if (1 === count($uriVariables) && array_key_exists('id', $uriVariables)) {
            $requirements = $operation->getRequirements();
            if (is_array($requirements) && !empty($requirements)) {
                $firstRequirementKey = array_keys($requirements)[0];

                return [$firstRequirementKey => $uriVariables['id']];
            }
        }

        return $uriVariables;
    }
}
