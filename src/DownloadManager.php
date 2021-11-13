<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotDownloadFile;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadManager
{
    public const IMAGE_RESIZE = 'r';
    public const IMAGE_CROP = 'c';

    /**
     * @var string
     */
    private $publicPath;

    /**
     * @var Storage
     */
    private $storage;

    public function __construct(string $publicPath, Storage $storage)
    {
        $this->publicPath = $publicPath;
        $this->storage = $storage;
    }

    public function generateDownloadUrlForFile(Media $media, bool $friendly = false): string
    {
        if (!$media->isUploaded()) {
            return '';
        }

        if ($friendly) {
            return sprintf('/media/%d/%s', $media->getId(), $media->getOriginalName());
        }

        return $this->publicPath . '/' . $media->getPath();
    }

    public function generateDownloadUrlForModifiedImage(Media $media, int $width, int $height, string $mode, bool $friendly = false): string
    {
        if (!$media->isUploaded()) {
            return '';
        }

        if ($friendly) {
            return sprintf(
                '/media/%d/%s?w=%d&h=%d&m=%s',
                $media->getId(),
                $media->getOriginalName(),
                $width,
                $height,
                $mode
            );
        }

        try {
            $path = $this->getModifiedImagePath($media, $width, $height, $mode);
        } catch (CouldNotDownloadFile $e) {
            return '';
        }

        return $this->publicPath . '/' . $path;
    }

    /**
     * @throws CouldNotDownloadFile
     */
    public function downloadFile(Media $media): BinaryFileResponse
    {
        if (!$media->isUploaded()) {
            throw new CouldNotDownloadFile();
        }

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
        if (!$media->isUploaded()) {
            throw new CouldNotDownloadFile();
        }

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
