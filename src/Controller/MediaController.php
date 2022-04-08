<?php

namespace Lnorby\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\BadImageDimensions;
use Lnorby\MediaBundle\Exception\CouldNotDownloadFile;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Exception\FileAlreadyUploaded;
use Lnorby\MediaBundle\Exception\InvalidFile;
use Lnorby\MediaBundle\Exception\NoFile;
use Lnorby\MediaBundle\Exception\UploadSizeExceeded;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// TODO: translations
class MediaController
{
    /**
     * @var UploadManager
     */
    private $uploadManager;

    /**
     * @var DownloadManager
     */
    private $downloadManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(UploadManager $uploadManager, DownloadManager $downloadManager, EntityManagerInterface $entityManager)
    {
        $this->uploadManager = $uploadManager;
        $this->downloadManager = $downloadManager;
        $this->entityManager = $entityManager;
    }

    public function downloadModifiedImage(Request $request): Response
    {
        $id = $request->attributes->getInt('id');
        $width = $request->attributes->getInt('width');
        $height = $request->attributes->getInt('height');
        $mode = $request->attributes->get('mode');
        $name = $request->attributes->get('name');

        $media = $this->entityManager->find(Media::class, $id);

        if (!$media instanceof Media || $media->getName() !== $name) {
            throw new NotFoundHttpException();
        }

        if (0 === $width || 0 === $height || !in_array($mode, [DownloadManager::IMAGE_RESIZE, DownloadManager::IMAGE_CROP])) {
            throw new NotFoundHttpException();
        }

        try {
            return $this->downloadManager->downloadModifiedImage($media, $width, $height, $mode);
        } catch (CouldNotDownloadFile $e) {
            throw new NotFoundHttpException();
        }
    }

    public function downloadFile(Request $request): Response
    {
        $id = $request->attributes->getInt('id');
        $name = $request->attributes->get('name');

        $media = $this->entityManager->find(Media::class, $id);

        if (!$media instanceof Media || $media->getName() !== $name) {
            throw new NotFoundHttpException();
        }

        try {
            return $this->downloadManager->downloadFile($media);
        } catch (CouldNotDownloadFile $e) {
            throw new NotFoundHttpException();
        }
    }

    public function uploadFile(Request $request): Response
    {
        $file = $request->files->get('file');

        try {
            $media = $this->uploadManager->uploadFile($file);
        } catch (NoFile $e) {
            return $this->errorResponse('Nem adott meg fájlt.');
        } catch (UploadSizeExceeded $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni, mert túl nagy a mérete.');
        } catch (FileAlreadyUploaded $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni.');
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return new JsonResponse(
            [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'url' => $this->downloadManager->generateDownloadUrlForFile($media),
            ],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }

    public function uploadImage(Request $request): Response
    {
        $image = $request->files->get('image');
        $minWidth = $request->request->getInt('min_width');
        $minHeight = $request->request->getInt('min_height');

        try {
            $media = $this->uploadManager->uploadImage($image, $minWidth, $minHeight);
        } catch (NoFile $e) {
            return $this->errorResponse('Nem adott meg képet.');
        } catch (InvalidFile $e) {
            return $this->errorResponse('A megadott fájl nem kép.');
        } catch (BadImageDimensions $e) {
            return $this->errorResponse(sprintf('A képnek legalább %d×%d pixel méretűnek kell lennie.', $minWidth, $minHeight));
        } catch (UploadSizeExceeded $e) {
            return $this->errorResponse('A képet nem sikerült feltölteni, mert túl nagy a mérete.');
        } catch (FileAlreadyUploaded $e) {
            return $this->errorResponse('A képet nem sikerült feltölteni.');
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('A képet nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return new JsonResponse(
            [
                'id' => $media->getId(),
                'url' => $this->downloadManager->generateDownloadUrlForModifiedImage(
                    $media,
                    250,
                    250,
                    DownloadManager::IMAGE_CROP
                ),
            ],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }

    protected function errorResponse(string $message): Response
    {
        return new Response($message, 422);
    }
}
