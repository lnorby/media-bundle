<?php

namespace Lnorby\MediaBundle\Form\DataTransformer;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class MediaTransformer implements DataTransformerInterface
{
    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function transform($media)
    {
        if (!$media instanceof Media) {
            return '';
        }

        return $media->getId();
    }

    public function reverseTransform($mediaId)
    {
        if (!$mediaId) {
            return null;
        }

        try {
            $media = $this->mediaRepository->getById((int)$mediaId);
        } catch (CouldNotFindMedia $e) {
            throw new TransformationFailedException('Media does not exist.');
        }

        return $media;
    }
}
