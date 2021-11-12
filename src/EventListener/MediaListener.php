<?php

namespace Lnorby\MediaBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\MediaManager;

final class MediaListener
{
    /**
     * @var MediaManager
     */
    private $mediaManager;

    public function __construct(MediaManager $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    public function preRemove(Media $media, LifecycleEventArgs $eventArgs): void
    {
        $this->mediaManager->deleteFiles($media);
    }
}
