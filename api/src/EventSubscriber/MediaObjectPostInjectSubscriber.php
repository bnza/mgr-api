<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\Data\MediaObject;
use App\Service\MediaObjectThumbnailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vich\UploaderBundle\Event\Event as VicUploaderEvent;
use Vich\UploaderBundle\Event\Events as VicUploaderEvents;

class MediaObjectPostInjectSubscriber implements EventSubscriberInterface
{
    private ?MediaObject $media = null;

    public function __construct(private readonly MediaObjectThumbnailer $thumbnailer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            VicUploaderEvents::POST_UPLOAD => ['setFilePath', EventPriorities::POST_DESERIALIZE],
            KernelEvents::TERMINATE => ['generateThumbnail'],
        ];
    }

    public function setFilePath(VicUploaderEvent $event): void
    {
        $media = $event->getObject();
        if ($media instanceof MediaObject) {
            $this->media = $media;
        }
    }

    public function generateThumbnail(TerminateEvent $event): void
    {
        if ($this->media) {
            $this->thumbnailer->generateThumbnail($this->media);
        }
    }
}
