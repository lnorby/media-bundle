<?php

namespace Lnorby\MediaBundle\Service\ImageManipulator;

use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

final class ImageManipulatorUsingIntervention implements ImageManipulator
{
    public function resize(string $source, int $newWidth, int $newHeight, int $quality = 90, bool $convertToJpeg = false): string
    {
        $image = $this->prepareImage($source);
        $image->resize(
            $newWidth,
            $newHeight,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        if ($convertToJpeg) {
            return (string)ImageManagerStatic::canvas($newWidth, $newHeight, 'ffffff')
                ->insert($image)
                ->encode('jpg', $quality);
        }

        return (string)$image->encode(null, $quality);
    }

    public function crop(string $source, int $newWidth, int $newHeight, int $quality = 90, bool $convertToJpeg = false): string
    {
        $image = $this->prepareImage($source);
        $image->fit($newWidth, $newHeight);

        if ($convertToJpeg) {
            return (string)ImageManagerStatic::canvas($newWidth, $newHeight, 'ffffff')
                ->insert($image)
                ->encode('jpg', $quality);
        }

        return (string)$image->encode(null, $quality);
    }

    private function prepareImage(string $source): Image
    {
        try {
            $image = ImageManagerStatic::make($source);
        } catch (NotReadableException $e) {
            throw new CouldNotManipulateImage('Image is not readable.', 0, $e);
        }

        try {
            $image->orientate();
        } catch (\Exception $e) {
        }

        return $image;
    }
}
