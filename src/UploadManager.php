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
    public function uploadFile(string $name, string $path, string $mimeType): Media
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $newPath = $this->generateUniqueFilenameWithPath($extension);

        try {
            $this->storage->createFile($newPath, file_get_contents($path));
        } catch (\Exception $e) {
            throw new CouldNotUploadFile('', 0, $e);
        }

        return $this->mediaManager->createMedia($newPath, $this->convertToSafeFilename($name, $extension), $mimeType);
    }

    /**
     * @throws CouldNotUploadFile
     */
    public function uploadImage(string $name, string $path): Media
    {
        $newPath = $this->generateUniqueFilenameWithPath('jpg');

        try {
            $imageManipulator = new ImageManipulator($path);
            $imageManipulator->resize($this->imageWidth, $this->imageHeight);
            $imageManipulator->setQuality($this->quality);
            $imageManipulator->setFormat(ImageManipulator::FORMAT_JPEG);
            $optimizedImage = $imageManipulator->execute();

            $this->storage->createFile($newPath, $optimizedImage);
        } catch (\RuntimeException $e) {
            throw new CouldNotUploadFile('', 0, $e);
        }

        return $this->mediaManager->createMedia($newPath, $this->convertToSafeFilename($name, 'jpg'), 'image/jpeg');
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
