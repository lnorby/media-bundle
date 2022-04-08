<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotDownloadFile;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
            'lnorby_media_download_file',
            [
                'id' => $media->getId(),
                'name' => $media->getName(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function generateDownloadUrlForModifiedImage(Media $media, int $width, int $height, string $mode): string
    {
        return $this->urlGenerator->generate(
            'lnorby_media_download_modified_image',
            [
                'id' => $media->getId(),
                'width' => $width,
                'height' => $height,
                'mode' => $mode,
                'name' => $media->getName(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @throws CouldNotDownloadFile
     */
    public function downloadFile(Media $media): BinaryFileResponse
    {
        if (!$this->storage->fileExists($media->getPath())) {
            throw new CouldNotDownloadFile();
        }

        return $this->createFileResponse($media->getPath());
    }

    public function downloadModifiedImage(Media $media, int $width, int $height, string $mode): BinaryFileResponse
    {
        return $this->createFileResponse($this->getModifiedImagePath($media, $width, $height, $mode));
    }

    /**
     * @throws CouldNotDownloadFile
     */
    private function getModifiedImagePath(Media $media, int $width, int $height, string $mode): string
    {
        $path = sprintf(
            '%s/%s.%dx%d%s.%s',
            pathinfo($media->getPath(), PATHINFO_DIRNAME),
            pathinfo($media->getPath(), PATHINFO_FILENAME),
            $width,
            $height,
            $mode,
            pathinfo($media->getPath(), PATHINFO_EXTENSION)
        );

        if (!$this->storage->fileExists($path)) {
            if (!$media->isImage()) {
                throw new CouldNotDownloadFile();
            }

            try {
                $imageManipulator = new ImageManipulator($this->storage->getRealPath($media->getPath()));
            } catch (\RuntimeException $e) {
                throw new CouldNotDownloadFile();
            }

            if (self::IMAGE_RESIZE === $mode) {
                $imageManipulator->resize($width, $height);
            } else {
                $imageManipulator->crop($width, $height);
            }

            $modifiedImage = $imageManipulator->execute();
            $this->storage->createFile($path, $modifiedImage);
        }

        return $path;
    }

    private function createFileResponse(string $path): BinaryFileResponse
    {
        $response = new BinaryFileResponse($this->storage->getRealPath($path));
        $response->setPrivate();
        $response->setMaxAge(31536000);

        return $response;
    }
}
