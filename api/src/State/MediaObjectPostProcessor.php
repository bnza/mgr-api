<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Data\MediaObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class MediaObjectPostProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!is_object($data)) {
            throw new \InvalidArgumentException(sprintf('Expected an object, got "%s".', gettype($data)));
        }

        if (!$data instanceof MediaObject) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of "%s", got "%s".', MediaObject::class, get_class($data)));
        }

        $data->setUploadDate(new \DateTimeImmutable());
        $data->setSha256(hash_file('sha256', $data->getFile()->getPathname()));

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
