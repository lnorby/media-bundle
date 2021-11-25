<?php

namespace Lnorby\MediaBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Lnorby\MediaBundle\Entity\Media;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class MediaTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        $media = $this->entityManager->find(Media::class, $mediaId);

        if (null === $media) {
            throw new TransformationFailedException('Media does not exist.');
        }

        return $media;
    }
}
