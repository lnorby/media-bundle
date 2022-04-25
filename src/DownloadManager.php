<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// TODO: url generation should be at storage level
final class DownloadManager
{
    public const IMAGE_RESIZE = 'r';
    public const IMAGE_CROP = 'c';

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(Storage $storage, UrlGeneratorInterface $urlGenerator)
    {
        $this->storage = $storage;
        $this->urlGenerator = $urlGenerator;
    }

    public function generateDownloadUrlForFile(Media $media): string
    {
        return $this->urlGenerator->generate(
            'lnorby_media_download',
            [
                'path' => $media->getPath(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function generateDownloadUrlForModifiedImage(Media $media, int $width, int $height, string $mode): string
    {
        return $this->urlGenerator->generate(
            'lnorby_media_download',
            [
                'path' => $this->getModifiedImagePath($media, $width, $height, $mode),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function downloadFile(Media $media): BinaryFileResponse
    {
        if (!$this->storage->fileExists($media->getPath())) {
            throw new \RuntimeException('File does not exist.');
        }

        return $this->createFileResponse($media->getPath());
    }

    public function downloadModifiedImage(Media $media, int $width, int $height, string $mode): BinaryFileResponse
    {
        $path = $this->getModifiedImagePath($media, $width, $height, $mode);

        if (!$this->storage->fileExists($path)) {
            if (!$media->isImage()) {
                throw new \RuntimeException('Media is not an image.');
            }

            $imageManipulator = new ImageManipulator($this->storage->getRealPath($media->getPath()));

            if (self::IMAGE_RESIZE === $mode) {
                $imageManipulator->resize($width, $height);
            } else {
                $imageManipulator->crop($width, $height);
            }

            $modifiedImage = $imageManipulator->execute();
            $this->storage->createFile($path, $modifiedImage);
        }

        return $this->createFileResponse($path);
    }

    private function getModifiedImagePath(Media $media, int $width, int $height, string $mode): string
    {
        return sprintf(
            '%s/%s.%dx%d%s.%s',
            pathinfo($media->getPath(), PATHINFO_DIRNAME),
            pathinfo($media->getPath(), PATHINFO_FILENAME),
            $width,
            $height,
            $mode,
            pathinfo($media->getPath(), PATHINFO_EXTENSION)
        );
    }

    private function createFileResponse(string $path): BinaryFileResponse
    {
        return new BinaryFileResponse($this->storage->getRealPath($path), 200, [], false);
    }
}
