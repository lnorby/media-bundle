<?php

namespace Lnorby\MediaBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\MediaManager;

final class MediaListener
{
    public function __construct(private readonly MediaManager $mediaManager)
    {
    }

    public function preRemove(Media $media, LifecycleEventArgs $eventArgs): void
    {
        $this->mediaManager->deleteMediaFiles($media);
    }
}
