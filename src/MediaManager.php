<?php

namespace Lnorby\MediaBundle;

use Doctrine\ORM\EntityManagerInterface;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Storage\Storage;

final class MediaManager
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(Storage $storage, EntityManagerInterface $entityManager)
    {
        $this->storage = $storage;
        $this->entityManager = $entityManager;
    }

    public function createMedia(): Media
    {
        $media = new Media();

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    public function createUploadedMedia(string $path, string $originalName, string $mimeType): Media
    {
        $media = new Media();
        $media->uploaded($path, $originalName, $mimeType);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    public function fileUploaded(Media $media, string $path, string $originalName, string $mimeType): void
    {
        $media->uploaded($path, $originalName, $mimeType);
        $this->entityManager->flush();
    }

    public function deleteMedia(Media $media): void
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    public function deleteFiles(Media $media): void
    {
        if (!$media->isUploaded()) {
            return;
        }

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
