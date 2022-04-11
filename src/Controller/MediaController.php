<?php

namespace Lnorby\MediaBundle\Controller;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Exception\BadImageDimensions;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Exception\FileAlreadyUploaded;
use Lnorby\MediaBundle\Exception\InvalidFile;
use Lnorby\MediaBundle\Exception\NoFile;
use Lnorby\MediaBundle\Exception\UploadSizeExceeded;
use Lnorby\MediaBundle\Repository\MediaRepository;
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
     * @var MediaRepository
     */
    private $mediaRepository;

    public function __construct(UploadManager $uploadManager, DownloadManager $downloadManager, MediaRepository $mediaRepository)
    {
        $this->uploadManager = $uploadManager;
        $this->downloadManager = $downloadManager;
        $this->mediaRepository = $mediaRepository;
    }

    public function download(Request $request): Response
    {
        $path = $request->attributes->get('path');
        return new Response($path);

        if (preg_match('#^((?:[0-9a-f]{2}/){3}[0-9a-f]{10})\.(\d+)x(\d+)([rc])(\.[a-z0-9]+)$#', $path, $matches)) {
            try {
                $media = $this->mediaRepository->getByPath($matches[1] . $matches[5]);
            } catch (CouldNotFindMedia $e) {
                throw new NotFoundHttpException();
            }

            try {
                return $this->downloadManager->downloadModifiedImage($media, $matches[2], $matches[3], $matches[4]);
            } catch (\RuntimeException $e) {
                throw new NotFoundHttpException();
            }
        }

        throw new NotFoundHttpException();
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
            ]
        );
    }

    protected function errorResponse(string $message): Response
    {
        return new Response($message, 422);
    }
}
