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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validation;

// TODO: import file
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

    /**
     * @var SluggerInterface
     */
    private $slugger;

    public function __construct(int $imageWidth, int $imageHeight, int $quality, MediaManager $mediaManager, Storage $storage, SluggerInterface $slugger)
    {
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->quality = $quality;
        $this->mediaManager = $mediaManager;
        $this->storage = $storage;
        $this->slugger = $slugger;
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadFile(UploadedFile $file): Media
    {
        dd($file->isValid(), $file->isExecutable(), $file->guessExtension(), $file->guessClientExtension());
        $this->validateFile($file);

        $path = $this->generateUniqueFilenameWithPath($file->guessExtension());

        try {
            $this->storage->createFile($path, $file->getContent());
        } catch (\Exception $e) {
            throw new CouldNotUploadFile();
        }

        $name = $this->convertToSafeFilename($file->getClientOriginalName(), $file->guessExtension());
        $mimeType = $file->getMimeType();

        return $this->mediaManager->createMedia($path, $name, $mimeType);
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadImage(UploadedFile $image, int $minWidth = 0, int $minHeight = 0): Media
    {
        $this->validateFile($image);

        $validator = Validation::createValidator();
        $imageConstraints = new Image();

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

        $name = $this->convertToSafeFilename($image->getClientOriginalName(), 'jpg');
        $mimeType = 'image/jpeg';

        return $this->mediaManager->createMedia($path, $name, $mimeType);
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

    private function convertToSafeFilename(string $originalFilename, string $extension): string
    {
        return sprintf(
            '%s.%s',
            strtolower($this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME))),
            $extension
        );
    }
}
