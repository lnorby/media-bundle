<?php

namespace Lnorby\MediaBundle\Controller;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\BadImageDimensions;
use Lnorby\MediaBundle\Exception\CouldNotDownloadFile;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Exception\InvalidFile;
use Lnorby\MediaBundle\Exception\NoFile;
use Lnorby\MediaBundle\Exception\UploadSizeExceeded;
use Lnorby\MediaBundle\MediaManager;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// TODO: translations
class MediaController extends AbstractController
{
    /**
     * @Route("/media/{id}/{originalName<[^/]+>}", name="_media_download", methods={"GET"})
     */
    public function download(Media $media, string $originalName, Request $request, DownloadManager $downloadManager): Response
    {
        if ($media->getOriginalName() !== $originalName) {
            throw $this->createNotFoundException();
        }

        $width = $request->query->getInt('w');
        $height = $request->query->getInt('h');
        $mode = $request->query->get('m');

        if (0 !== $width && 0 !== $height && null !== $mode && in_array($mode, [DownloadManager::IMAGE_RESIZE, DownloadManager::IMAGE_CROP])) {
            try {
                return $downloadManager->downloadModifiedImage($media, $width, $height, $mode);
            } catch (CouldNotDownloadFile $e) {
                throw $this->createNotFoundException();
            }
        }

        try {
            return $downloadManager->downloadFile($media);
        } catch (CouldNotDownloadFile $e) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/_media/new", name="_media_new", methods={"POST"})
     */
    public function new(MediaManager $mediaManager): Response
    {
        $media = $mediaManager->createMedia();

        return new Response($media->getId());
    }

    /**
     * @Route("/_media/{id}/delete", name="_media_delete", methods={"GET"})
     */
    public function delete(Media $media, MediaManager $mediaManager): Response
    {
        $mediaManager->deleteMedia($media);

        return new Response();
    }

    /**
     * @Route("/_media/{id}/upload-file", name="_media_upload_file", methods={"POST"})
     */
    public function uploadFile(Media $media, Request $request, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        $file = $request->files->get('file');

        try {
            $uploadManager->uploadFile($file, $media);
        } catch (NoFile $e) {
            return $this->errorResponse('Nem adott meg fájlt.');
        } catch (UploadSizeExceeded $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni, mert túl nagy a mérete.');
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return new Response($downloadManager->generateDownloadUrlForFile($media));
    }

    /**
     * @Route("/_media/{id}/upload-image", name="_media_upload_image", methods={"POST"})
     */
    public function uploadImage(Media $media, Request $request, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        $image = $request->files->get('image');
        $minWidth = $request->request->getInt('min_width');
        $minHeight = $request->request->getInt('min_height');

        try {
            $uploadManager->uploadImage($image, $media, $minWidth, $minHeight);
        } catch (NoFile $e) {
            return $this->errorResponse('Nem adott meg képet.');
        } catch (InvalidFile $e) {
            return $this->errorResponse('A megadott fájl nem kép.');
        } catch (BadImageDimensions $e) {
            return $this->errorResponse(sprintf('A képnek legalább %d×%d pixel méretűnek kell lennie.', $minWidth, $minHeight));
        } catch (UploadSizeExceeded $e) {
            return $this->errorResponse('A képet nem sikerült feltölteni, mert túl nagy a mérete.');
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('A képet nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return new Response($downloadManager->generateDownloadUrlForFile($media));
    }

    protected function errorResponse(string $message): Response
    {
        return new Response($message, 422);
    }
}
