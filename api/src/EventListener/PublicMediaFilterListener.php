<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatically enables the public_media SQL filter for unauthenticated users.
 *
 * This listener is the trigger for the "fail-safe" filtering workflow. It ensures that
 * at the very start of a request, if no user is fully authenticated, the SQL-level
 * filtering for public MediaObjects (PublicMediaObjectSqlFilter) is activated
 * across the entire application context.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
final readonly class PublicMediaFilterListener
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->em->getFilters()->enable('public_media');
        }
    }
}
