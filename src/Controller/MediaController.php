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
use Lnorby\MediaBundle\Repository\MediaRepository;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// TODO: translations
class MediaController extends AbstractController
{
//    /**
//     * @Route("/media/{id}/{width}/{height}/{mode}/{name<[^/]+>}", name="_media_download_modified_image", methods={"GET"})
//     */
    public function downloadModifiedImage(RequestStack $requestStack, EntityManagerInterface $entityManager, DownloadManager $downloadManager): Response
    {
        $request = $requestStack->getCurrentRequest();
        $id = $request->attributes->getInt('id');
        $width = $request->attributes->getInt('width');
        $height = $request->attributes->getInt('height');
        $mode = $request->attributes->get('mode');
        $name = $request->attributes->get('name');

        $media = $entityManager->find(Media::class, $id);

        if (!$media instanceof Media) {
            throw $this->createNotFoundException();
        }

        if ($media->getName() !== $name) {
            throw $this->createNotFoundException();
        }

        if (0 === $width || 0 === $height || !in_array($mode, [DownloadManager::IMAGE_RESIZE, DownloadManager::IMAGE_CROP])) {
            throw $this->createNotFoundException();
        }

        try {
            return $downloadManager->downloadModifiedImage($media, $width, $height, $mode);
        } catch (CouldNotDownloadFile $e) {
            throw $this->createNotFoundException();
        }
    }

//    /**
//     * @Route("/media/{id}/{name<[^/]+>}", name="_media_download_file", methods={"GET"})
//     */
    public function downloadFile(RequestStack $requestStack, EntityManagerInterface $entityManager, DownloadManager $downloadManager): Response
    {
        $request = $requestStack->getCurrentRequest();
        $id = $request->attributes->getInt('id');
        $name = $request->attributes->get('name');

        $media = $entityManager->find(Media::class, $id);

        if (!$media instanceof Media) {
            throw $this->createNotFoundException();
        }

        if ($media->getName() !== $name) {
            throw $this->createNotFoundException();
        }

        try {
            return $downloadManager->downloadFile($media);
        } catch (CouldNotDownloadFile $e) {
            throw $this->createNotFoundException();
        }
    }

//    /**
//     * @Route("/_media/upload-file", name="_media_upload_file", methods={"POST"})
//     */
    public function uploadFile(RequestStack $requestStack, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        $request = $requestStack->getCurrentRequest();

        $file = $request->files->get('file');

        try {
            $media = $uploadManager->uploadFile($file);
        } catch (NoFile $e) {
            return $this->errorResponse('Nem adott meg fájlt.');
        } catch (UploadSizeExceeded $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni, mert túl nagy a mérete.');
        } catch (FileAlreadyUploaded $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni.');
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('A fájlt nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return $this->json(
            [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'url' => $downloadManager->generateDownloadUrlForFile($media),
            ]
        );
    }

//    /**
//     * @Route("/_media/upload-image", name="_media_upload_image", methods={"POST"})
//     */
    public function uploadImage(RequestStack $requestStack, UploadManager $uploadManager, DownloadManager $downloadManager): Response
    {
        $request = $requestStack->getCurrentRequest();

        $image = $request->files->get('image');
        $minWidth = $request->request->getInt('min_width');
        $minHeight = $request->request->getInt('min_height');

        try {
            $media = $uploadManager->uploadImage($image, $minWidth, $minHeight);
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

        return $this->json(
            [
                'id' => $media->getId(),
                'url' => $downloadManager->generateDownloadUrlForModifiedImage(
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
