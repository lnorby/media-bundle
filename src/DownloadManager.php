<?php

namespace Lnorby\MediaBundle;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Storage\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\RouterInterface;

final class DownloadManager
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(Storage $storage, RouterInterface $router)
    {
        $this->storage = $storage;
        $this->router = $router;
    }

    public function generateDownloadUrl(Media $media): ?string
    {
        if (!$media->isUploaded()) {
            return null;
        }

        return $this->router->generate(
            '_media_download',
            [
                'id' => $media->getId(),
                'originalName' => $media->getOriginalName(),
            ]
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function downloadFile(Media $media): BinaryFileResponse
    {
        if (!$media->isUploaded()) {
            throw new \RuntimeException('File not uploaded.');
        }

        if (!$this->storage->fileExists($media->getPath())) {
            throw new \RuntimeException('File does not exist.');
        }

        return $this->createFileResponse($media->getPath());
    }

    /**
     * @throws \RuntimeException
     */
    public function downloadImage(Media $media, int $width, int $height, string $mode): BinaryFileResponse
    {
        if (!$media->isUploaded()) {
            throw new \RuntimeException('File not uploaded.');
        }

        if (!$this->storage->fileExists($media->getPath())) {
            throw new \RuntimeException('File does not exist.');
        }

        if (!$media->isImage()) {
            throw new \RuntimeException('This media is not an image.');
        }

        $path = sprintf(
            '%s/%s.%dx%d%s.%s',
            pathinfo($media->getPath(), PATHINFO_DIRNAME),
            pathinfo($media->getPath(), PATHINFO_FILENAME),
            $width,
            $height,
            $mode,
            pathinfo($media->getPath(), PATHINFO_EXTENSION)
        );

        if ($this->storage->fileExists($path)) {
            return $this->createFileResponse($path);
        }

        $imageManipulator = new ImageManipulator($this->storage->getRealPath($media->getPath()));

        if ('r' === $mode) {
            $imageManipulator->resize($width, $height);
        } else {
            $imageManipulator->crop($width, $height);
        }

        $resizedImage = $imageManipulator->execute();

        $this->storage->createFile($path, $resizedImage);

        return $this->createFileResponse($path);
    }

    private function createFileResponse(string $path): BinaryFileResponse
    {
        // TODO: better cache
        $response = new BinaryFileResponse($this->storage->getRealPath($path));
        $response->setMaxAge(2592000);

        return $response;
    }
}
