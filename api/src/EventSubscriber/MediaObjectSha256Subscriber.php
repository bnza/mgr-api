<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\Data\MediaObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class MediaObjectSha256Subscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['calculateSha256', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function calculateSha256(ViewEvent $event): void
    {
        $data = $event->getControllerResult();

        if (!$data instanceof MediaObject) {
            return;
        }

        // Only process POST operations (creation)
        if ('POST' !== $event->getRequest()->getMethod()) {
            return;
        }

        // Calculate SHA256 before validation
        if ($data->getFile()) {
            $data->setSha256(hash_file('sha256', $data->getFile()->getPathname()));
        }
    }
}
