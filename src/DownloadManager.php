<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Lnorby\MediaBundle\Service\ImageManipulator\CouldNotManipulateImage;
use Lnorby\MediaBundle\Service\ImageManipulator\ImageManipulator;
use Lnorby\MediaBundle\Service\Storage\Storage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DownloadManager
{
    public const IMAGE_RESIZE = 'r';
    public const IMAGE_CROP = 'c';

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly Storage $storage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ImageManipulator $imageManipulator
    ) {
    }

    public function downloadUrlForMediaFile(Media $media, bool $withDomain = true): string
    {
        return $this->urlGenerator->generate(
            'lnorby_media_download',
            [
                'path' => $media->path(),
            ],
            $withDomain ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    public function downloadUrlForMediaModifiedImage(Media $media, int $width, int $height, string $mode): string
    {
        return $this->urlGenerator->generate(
            'lnorby_media_download',
            [
                'path' => $this->modifiedImagePath($media->path(), $width, $height, $mode),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getRealPathFromPublicPath(string $publicPath): string
    {
        if ($this->storage->fileExists($publicPath)) {
            return $this->storage->getRealPath($publicPath);
        }

        $isPathToAModifiedImage = preg_match(
            '#^((?:[0-9a-f]{2}/){3}[0-9a-f]{10})\.(\d+)x(\d+)([rc])(\.[a-z0-9]+)$#',
            $publicPath,
            $matches
        );

        if (!$isPathToAModifiedImage) {
            throw new \RuntimeException('Could not find file.');
        }

        list(, $filename, $width, $height, $mode, $fileExtension) = $matches;

        try {
            $media = $this->mediaRepository->getByPath($filename . $fileExtension);
        } catch (CouldNotFindMedia) {
            throw new \RuntimeException('Could not find media.');
        }

        $this->modifyImage($media, $publicPath, $width, $height, $mode);

        return $this->storage->getRealPath($publicPath);
    }

    private function modifyImage(Media $media, string $destination, int $width, int $height, string $mode): void
    {
        if (!$media->isImage()) {
            throw new \InvalidArgumentException(sprintf('Media "%d" is not an image.', $media->id()));
        }

        try {
            if (self::IMAGE_RESIZE === $mode) {
                $modifiedImage = $this->imageManipulator->resize(
                    $this->storage->getRealPath($media->path()),
                    $width,
                    $height
                );
            } else {
                $modifiedImage = $this->imageManipulator->crop(
                    $this->storage->getRealPath($media->path()),
                    $width,
                    $height
                );
            }
        } catch (CouldNotManipulateImage) {
            throw new \RuntimeException('Could not modify image.');
        }

        try {
            $this->storage->createFile($destination, $modifiedImage);
        } catch (\RuntimeException) {
            throw new \RuntimeException('Could not write image data to file.');
        }
    }

    private function modifiedImagePath(string $originalPath, int $width, int $height, string $mode): string
    {
        return sprintf(
            '%s/%s.%dx%d%s.%s',
            pathinfo($originalPath, PATHINFO_DIRNAME),
            pathinfo($originalPath, PATHINFO_FILENAME),
            $width,
            $height,
            $mode,
            pathinfo($originalPath, PATHINFO_EXTENSION)
        );
    }
}
