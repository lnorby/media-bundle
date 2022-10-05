<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Lnorby\MediaBundle\Storage\Storage;

final class MediaManager
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    public function __construct(Storage $storage, MediaRepository $mediaRepository)
    {
        $this->storage = $storage;
        $this->mediaRepository = $mediaRepository;
    }

    public function createMedia(string $path, string $originalName, string $mimeType): Media
    {
        $media = new Media($path, $originalName, $mimeType);
        $this->mediaRepository->add($media);

        return $media;
    }

    public function deleteMedia(Media $media): void
    {
        $this->mediaRepository->remove($media);
    }

    public function deleteFiles(Media $media): void
    {
        $pattern = sprintf(
            '%s/%s.*.%s',
            pathinfo($media->getPath(), PATHINFO_DIRNAME),
            pathinfo($media->getPath(), PATHINFO_FILENAME),
            pathinfo($media->getPath(), PATHINFO_EXTENSION)
        );

        foreach ($this->storage->search($pattern) as $file) {
            $this->storage->deleteFile($file);
        }

        $this->storage->deleteFile($media->getPath());
    }
}
