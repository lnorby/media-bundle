<?php

namespace Lnorby\MediaBundle\Form\Dto;

use Lnorby\MediaBundle\Entity\Media;

final class UploadedImageDto
{
    public $entity;
    public Media $media;
    public $position;
}
