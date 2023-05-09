<?php

namespace Lnorby\MediaBundle\Form\DataTransformer;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class MediaTransformer implements DataTransformerInterface
{
    public function __construct(private readonly MediaRepository $mediaRepository)
    {
    }

    public function transform($value)
    {
        if (!$value instanceof Media) {
            return '';
        }

        return $value->id();
    }

    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        try {
            $media = $this->mediaRepository->getById((int)$value);
        } catch (CouldNotFindMedia) {
            throw new TransformationFailedException('Media does not exist.');
        }

        return $media;
    }
}
