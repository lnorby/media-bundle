<?php

namespace Lnorby\MediaBundle\Form\Dto;

use Lnorby\MediaBundle\Entity\Media;

final class UploadedImageDto
{
    public $entity;

    /**
     * @var Media
     */
    public $media;

    public $position;
}
