<?php

namespace Lnorby\MediaBundle\Service\ImageManipulator;

interface ImageManipulator
{
    /**
     * @throws CouldNotManipulateImage
     */
    public function resize(string $source, int $newWidth, int $newHeight, int $quality = 100, bool $convertToJpeg = false): string;

    /**
     * @throws CouldNotManipulateImage
     */
    public function crop(string $source, int $newWidth, int $newHeight, int $quality = 100, bool $convertToJpeg = false): string;
}
