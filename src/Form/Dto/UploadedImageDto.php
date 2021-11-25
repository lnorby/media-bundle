<?php

namespace Lnorby\MediaBundle\Form\Dto;

use Lnorby\MediaBundle\Entity\Media;

final class UploadedImageDto
{
    public $entity;

    public $media;

    public $position;

    public static function create($entity, Media $media, ?int $position = null): self
    {
        $instance = new self();
        $instance->entity = $entity;
        $instance->media = $media;
        $instance->position = $position;

        return $instance;
    }
}
