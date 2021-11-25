<?php

namespace Lnorby\MediaBundle\Form\Dto;

final class UploadedImageDto
{
    public $entity;

    public $mediaId;

    public $position;

    public static function create($entity, int $mediaId, ?int $position = null): self
    {
        $instance = new self();
        $instance->entity = $entity;
        $instance->mediaId = $mediaId;
        $instance->position = $position;

        return $instance;
    }
}
