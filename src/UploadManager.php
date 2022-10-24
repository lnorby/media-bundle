<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Service\FilenameGenerator;
use Lnorby\MediaBundle\Service\ImageManipulator\ImageManipulator;
use Lnorby\MediaBundle\Service\Storage\Storage;

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
     * @var FilenameGenerator
     */
    private $filenameGenerator;

    /**
     * @var ImageManipulator
     */
    private $imageManipulator;

    public function __construct(int $imageWidth, int $imageHeight, int $quality, MediaManager $mediaManager, Storage $storage, FilenameGenerator $filenameGenerator, ImageManipulator $imageManipulator)
    {
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->quality = $quality;
        $this->mediaManager = $mediaManager;
        $this->storage = $storage;
        $this->filenameGenerator = $filenameGenerator;
        $this->imageManipulator = $imageManipulator;
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadFileAndCreateMedia(string $name, string $content, string $mimeType): Media
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $path = $this->filenameGenerator->generateUniqueFilenameWithPath($extension);

        try {
            $this->storage->createFile($path, $content);
        } catch (\RuntimeException $e) {
            throw new CouldNotUploadFile('', 0, $e);
        }

        return $this->mediaManager->createMedia(
            $path,
            $this->filenameGenerator->convertToSafeFilename($name, $extension),
            $mimeType
        );
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadImageAndCreateMedia(string $name, string $content): Media
    {
        $path = $this->filenameGenerator->generateUniqueFilenameWithPath('jpg');

        try {
            $resizedImage = $this->imageManipulator->resize(
                $content,
                $this->imageWidth,
                $this->imageHeight,
                $this->quality,
                true
            );
            $this->storage->createFile($path, $resizedImage);
        } catch (\RuntimeException $e) {
            throw new CouldNotUploadFile('', 0, $e);
        }

        return $this->mediaManager->createMedia(
            $path,
            $this->filenameGenerator->convertToSafeFilename($name, 'jpg'),
            'image/jpeg'
        );
    }
}
