<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Data\MediaObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class MediaObjectPostProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
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

        // SHA256 is generated in MediaObjectSha256Subscriber class
        $data->setUploadDate(new \DateTimeImmutable());
        $data->setUploadedBy($this->security->getUser());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
