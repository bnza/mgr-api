<?php

namespace App\State;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;

readonly class SubResourcePostProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $operationLinks = $operation->getUriVariables();
        if (0 === count($operationLinks) || !isset($operationLinks['parentId'])) {
            throw new \RuntimeException(sprintf('parentId link key missing in "%s" operation.', $operation->getName()));
        }

        $operationParentLink = $operationLinks['parentId'];

        if (!($operationParentLink instanceof Link)) {
            throw new \InvalidArgumentException(sprintf('parentId link must be an instance of "%s" for the "%s" operation: "%s" given.', Link::class, $operation->getName(), get_debug_type($operationParentLink)));
        }

        $fromClass = $operationParentLink->getFromClass();

        if (!$fromClass) {
            throw new \InvalidArgumentException(sprintf('parentId link must have a fromClass for the "%s" operation.', $operation->getName()));
        }

        $toProperty = $operationParentLink->getToProperty();

        if (!$toProperty) {
            throw new \InvalidArgumentException(sprintf('parentId link must have a toProperty for the "%s" operation.', $operation->getName()));
        }

        if (!isset($uriVariables['parentId'])) {
            throw new \InvalidArgumentException(sprintf('parentId uriVariable missing for the "%s" operation.', $operation->getName()));
        }

        $parent = $this->entityManager->find($fromClass, $uriVariables['parentId']);

        if (!$parent) {
            throw new NotFoundHttpException();
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyAccessor->setValue($data, $toProperty, $parent);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
