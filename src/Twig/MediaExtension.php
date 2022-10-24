<?php

namespace Lnorby\MediaBundle\Twig;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MediaExtension extends AbstractExtension
{
    /**
     * @var DownloadManager
     */
    private $downloadManager;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    public function __construct(DownloadManager $downloadManager, MediaRepository $mediaRepository)
    {
        $this->downloadManager = $downloadManager;
        $this->mediaRepository = $mediaRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_file', [$this, 'file']),
            new TwigFunction('media_resized_image', [$this, 'resizedImage']),
            new TwigFunction('media_cropped_image', [$this, 'croppedImage']),
        ];
    }

    public function file($media): string
    {
        if (!$media instanceof Media) {
            try {
                $media = $this->mediaRepository->getById((int)$media);
            } catch (CouldNotFindMedia $e) {
                return '';
            }
        }

        return $this->downloadManager->downloadUrlForMediaFile($media);
    }

    public function resizedImage($media, int $width, int $height): string
    {
        if (!$media instanceof Media) {
            try {
                $media = $this->mediaRepository->getById((int)$media);
            } catch (CouldNotFindMedia $e) {
                return '';
            }
        }

        return $this->downloadManager->downloadUrlForMediaModifiedImage(
            $media,
            $width,
            $height,
            DownloadManager::IMAGE_RESIZE
        );
    }

    public function croppedImage($media, int $width, int $height): string
    {
        if (!$media instanceof Media) {
            try {
                $media = $this->mediaRepository->getById((int)$media);
            } catch (CouldNotFindMedia $e) {
                return '';
            }
        }

        return $this->downloadManager->downloadUrlForMediaModifiedImage(
            $media,
            $width,
            $height,
            DownloadManager::IMAGE_CROP
        );
    }
}
