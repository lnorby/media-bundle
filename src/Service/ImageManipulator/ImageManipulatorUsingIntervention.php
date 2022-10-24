<?php

namespace Lnorby\MediaBundle\Service\ImageManipulator;

use Intervention\Image\Constraint;
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
            function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        return $this->encodeImage($image, $quality, $convertToJpeg);
    }

    public function crop(string $source, int $newWidth, int $newHeight, int $quality = 90, bool $convertToJpeg = false): string
    {
        $image = $this->prepareImage($source);
        $image->fit($newWidth, $newHeight);

        return $this->encodeImage($image, $quality, $convertToJpeg);
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

    private function encodeImage(Image $image, int $quality, bool $convertToJpeg): string
    {
        if ($convertToJpeg) {
            return (string)ImageManagerStatic::canvas($image->getWidth(), $image->getHeight(), 'ffffff')
                ->insert($image)
                ->encode('jpg', $quality);
        }

        return (string)$image->encode(null, $quality);
    }
}
