<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Lnorby\MediaBundle\Service\Storage\Storage;

final class MediaManager
{
    public function __construct(private readonly Storage $storage, private readonly MediaRepository $mediaRepository)
    {
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

    public function deleteMediaFiles(Media $media): void
    {
        $pattern = sprintf(
            '%s/%s.*.%s',
            pathinfo($media->path(), PATHINFO_DIRNAME),
            pathinfo($media->path(), PATHINFO_FILENAME),
            pathinfo($media->path(), PATHINFO_EXTENSION)
        );

        foreach ($this->storage->search($pattern) as $file) {
            $this->storage->deleteFile($file);
        }

        $this->storage->deleteFile($media->path());
    }
}
