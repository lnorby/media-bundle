<?php

namespace Lnorby\MediaBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Entity\Media;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MediaExtension extends AbstractExtension
{
    /**
     * @var DownloadManager
     */
    private $downloadManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(DownloadManager $downloadManager, EntityManagerInterface $entityManager)
    {
        $this->downloadManager = $downloadManager;
        $this->entityManager = $entityManager;
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
            $media = $this->entityManager->find(Media::class, $media);
        }

        if (!$media instanceof Media) {
            return '';
        }

        return $this->downloadManager->generateDownloadUrl($media) ?? '';
    }

    public function resizedImage($media, int $width, int $height): string
    {
        if (!$media instanceof Media) {
            $media = $this->entityManager->find(Media::class, $media);
        }

        if (!$media instanceof Media) {
            return '';
        }

        $downloadUrl = $this->downloadManager->generateDownloadUrl($media);

        if (null === $downloadUrl) {
            return '';
        }

        return sprintf(
            '%s?w=%d&h=%d&m=r',
            $downloadUrl,
            $width,
            $height
        );
    }

    public function croppedImage($media, int $width, int $height): string
    {
        if (!$media instanceof Media) {
            $media = $this->entityManager->find(Media::class, $media);
        }

        if (!$media instanceof Media) {
            return '';
        }

        $downloadUrl = $this->downloadManager->generateDownloadUrl($media);

        if (null === $downloadUrl) {
            return '';
        }

        return sprintf(
            '%s?w=%d&h=%d&m=c',
            $downloadUrl,
            $width,
            $height
        );
    }
}
