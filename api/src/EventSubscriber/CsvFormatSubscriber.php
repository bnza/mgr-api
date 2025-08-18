<?php

namespace App\EventSubscriber;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CsvFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', EventPriorities::PRE_READ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_api_resource_class')) {
            return;
        }

        $format = $request->getRequestFormat();

        if ('csv' === $format) {
            /** @var GetCollection $operation */
            $operation = $request->attributes->get('_api_operation');

            $request->attributes->set(
                '_api_operation',
                $operation->withPaginationEnabled(false)
            );
        }
    }
}
