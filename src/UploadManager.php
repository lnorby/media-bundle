<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function uploadFile(string $name, string $content, string $mimeType): Media
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $path = $this->generateUniqueFilenameWithPath($extension);

        try {
            $this->storage->createFile($path, $content);
        } catch (\Exception $e) {
            throw new CouldNotUploadFile();
        }

        return $this->mediaManager->createMedia($path, $this->convertToSafeFilename($name, $extension), $mimeType);
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadImage(string $name, string $content): Media
    {
        $path = $this->generateUniqueFilenameWithPath('jpg');

        try {
            $imageManipulator = new ImageManipulator($content);
            $imageManipulator->resize($this->imageWidth, $this->imageHeight);
            $imageManipulator->setQuality($this->quality);
            $imageManipulator->setFormat(ImageManipulator::FORMAT_JPEG);
            $optimizedImage = $imageManipulator->execute();

            $this->storage->createFile($path, $optimizedImage);
        } catch (\RuntimeException $e) {
            throw new CouldNotUploadFile();
        }

        return $this->mediaManager->createMedia($path, $this->convertToSafeFilename($name, 'jpg'), 'image/jpeg');
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
