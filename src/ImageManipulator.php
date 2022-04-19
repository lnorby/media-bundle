<?php

namespace Lnorby\MediaBundle;

use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

final class ImageManipulator
{
    public const FORMAT_JPEG = 'jpg';
    public const FORMAT_PNG = 'png';
    public const FORMAT_WEBP = 'webp';

    /**
     * @var Image
     */
    private $image;

    /**
     * @var int|null
     */
    private $quality = null;

    /**
     * @var string|null
     */
    private $format = null;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $source)
    {
        try {
            $this->image = ImageManagerStatic::make($source);
        } catch (NotReadableException $e) {
            throw new \RuntimeException('Image is not readable.', 0, $e);
        }

        try {
            $this->image->orientate();
        } catch (\Exception $e) {
        }
    }

    public function crop(int $width, int $height): void
    {
        $this->image->fit($width, $height);
    }

    public function execute(): string
    {
        if (self::FORMAT_JPEG === $this->format) {
            return (string)ImageManagerStatic::canvas($this->image->getWidth(), $this->image->getHeight(), 'ffffff')
                ->insert($this->image)
                ->encode($this->format, $this->quality);
        }
        
        return (string)$this->image->encode($this->format, $this->quality);
    }

    public function resize(?int $width, ?int $height): void
    {
        $this->image->resize(
            $width,
            $height,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
    }

    public function setFormat(string $format): void
    {
        if (!in_array($format, [self::FORMAT_JPEG, self::FORMAT_PNG, self::FORMAT_WEBP])) {
            throw new \InvalidArgumentException('Invalid format.');
        }

        $this->format = $format;
    }

    public function setQuality(int $quality): void
    {
        $this->quality = $quality;
    }
}
