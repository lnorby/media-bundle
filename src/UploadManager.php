<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\BadImageDimensions;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Exception\InvalidFile;
use Lnorby\MediaBundle\Exception\NoFile;
use Lnorby\MediaBundle\Exception\UploadSizeExceeded;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validation;

final class UploadManager
{
    /**
     * @var int
     */
    private $imageWidth;

    /**
     * @var int
     */
    private $imageHeight;

    /**
     * @var int
     */
    private $quality;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var Storage
     */
    private $storage;

    public function __construct(int $imageWidth, int $imageHeight, int $quality, MediaManager $mediaManager, Storage $storage)
    {
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->quality = $quality;
        $this->mediaManager = $mediaManager;
        $this->storage = $storage;
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadFile(UploadedFile $file, ?Media $media = null): Media
    {
        $this->validateFile($file);

        $path = $this->generateUniqueFilenameWithPath($file->guessExtension());

        try {
            $this->storage->createFile($path, $file->getContent());
        } catch (\Exception $e) {
            throw new CouldNotUploadFile();
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        if ($media instanceof Media) {
            $this->mediaManager->fileUploaded($media, $path, $originalName, $mimeType);
        } else {
            $media = $this->mediaManager->createUploadedMedia($path, $originalName, $mimeType);
        }

        return $media;
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadImage(UploadedFile $image, ?Media $media = null, int $minWidth = 0, int $minHeight = 0): Media
    {
        $this->validateFile($image);

        $validator = Validation::createValidator();
        $imageConstraints = new Image();
        $imageConstraints->detectCorrupted = true;

        if (0 !== count($validator->validate($image, [$imageConstraints]))) {
            throw new InvalidFile();
        }

        if (0 !== $minWidth) {
            $imageConstraints->minWidth = $minWidth;
        }

        if (0 !== $minHeight) {
            $imageConstraints->minHeight = $minHeight;
        }

        if ((0 !== $minWidth || 0 !== $minHeight) && 0 !== count($validator->validate($image, [$imageConstraints]))) {
            throw new BadImageDimensions();
        }

        $path = $this->generateUniqueFilenameWithPath('jpg');

        try {
            $imageManipulator = new ImageManipulator($image->getContent());
            $imageManipulator->resize($this->imageWidth, $this->imageHeight);
            $imageManipulator->setQuality($this->quality);
            $imageManipulator->setFormat(ImageManipulator::FORMAT_JPEG);
            $optimizedImage = $imageManipulator->execute();

            $this->storage->createFile($path, $optimizedImage);
        } catch (\Exception $e) {
            throw new CouldNotUploadFile();
        }

        $originalName = $image->getClientOriginalName();
        $mimeType = $image->getMimeType();

        if ($media instanceof Media) {
            $this->mediaManager->fileUploaded($media, $path, $originalName, $mimeType);
        } else {
            $media = $this->mediaManager->createUploadedMedia($path, $originalName, $mimeType);
        }

        return $media;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            switch ($file->getError()) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new UploadSizeExceeded();
                case UPLOAD_ERR_NO_FILE:
                    throw new NoFile();
                default:
                    throw new CouldNotUploadFile();
            }
        }
    }

    private function generateUniqueFilenameWithPath(string $extension): string
    {
        $uniqueFilename = bin2hex(random_bytes(8));

        return sprintf(
            '%s/%s/%s/%s.%s',
            substr($uniqueFilename, 0, 2),
            substr($uniqueFilename, 2, 2),
            substr($uniqueFilename, 4, 2),
            substr($uniqueFilename, 6),
            $extension
        );
    }
}
