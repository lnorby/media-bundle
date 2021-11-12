<?php

namespace Lnorby\MediaBundle\Controller;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\BadImageDimensions;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Exception\InvalidFile;
use Lnorby\MediaBundle\Exception\NoFile;
use Lnorby\MediaBundle\Exception\UploadSizeExceeded;
use Lnorby\MediaBundle\MediaManager;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// TODO: protect with secret
// TODO: translations

class MediaController extends AbstractController
{
    /**
     * @Route("/{id}/{originalName}", name="_media_download", methods={"GET"}, priority=-1)
     */
    public function download(Media $media, string $originalName, Request $request, DownloadManager $downloadManager): Response
    {
        if ($media->getOriginalName() !== $originalName) {
            throw $this->createNotFoundException();
        }

        $width = $request->query->getInt('w');
        $height = $request->query->getInt('h');
        $mode = $request->query->getInt('m');

        if (0 !== $width && 0 !== $height && in_array($mode, ['r', 'c'])) {
            try {
                return $downloadManager->downloadImage($media, $width, $height, $mode);
            } catch (\RuntimeException $e) {
                throw $this->createNotFoundException();
            }
        }

        try {
            return $downloadManager->downloadFile($media);
        } catch (\RuntimeException $e) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/new", name="_media_new", methods={"POST"})
     */
    public function new(MediaManager $mediaManager): Response
    {
        $media = $mediaManager->createMedia();

        return new Response($media->getId());
    }

    /**
     * @Route("/{id}/delete", name="_media_delete", methods={"GET"})
     */
    public function delete(Media $media, MediaManager $mediaManager): Response
    {
        $mediaManager->deleteMedia($media);

        return new Response();
    }

    /**
     * @Route("/{id}/upload-file", name="_media_upload_file", methods={"POST"})
     */
    public function uploadFile(Media $media, Request $request, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        /**
         * @var UploadedFile $file
         */
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

        return new Response($downloadManager->generateDownloadUrl($media));
    }

    /**
     * @Route("/{id}/upload-image", name="_media_upload_image", methods={"POST"})
     */
    public function uploadImage(Media $media, Request $request, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        /**
         * @var UploadedFile $image
         */
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

        return new Response($downloadManager->generateDownloadUrl($media));
    }

    protected function errorResponse(string $message): Response
    {
        return new Response($message, 422);
    }
}
