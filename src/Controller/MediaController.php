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
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DownloadManager
     */
    private $downloadManager;

    /**
     * @var UploadManager
     */
    private $uploadManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, DownloadManager $downloadManager, UploadManager $uploadManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->downloadManager = $downloadManager;
        $this->uploadManager = $uploadManager;
    }

//    /**
//     * @Route("/media/{id}/{width}/{height}/{mode}/{name<[^/]+>}", name="_media_download_modified_image", methods={"GET"})
//     */
    public function downloadModifiedImage(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $id = $request->attributes->getInt('id');
        $width = $request->attributes->getInt('width');
        $height = $request->attributes->getInt('height');
        $mode = $request->attributes->get('mode');
        $name = $request->attributes->get('name');

        $media = $this->entityManager->find(Media::class, $id);

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
            return $this->downloadManager->downloadModifiedImage($media, $width, $height, $mode);
        } catch (CouldNotDownloadFile $e) {
            throw $this->createNotFoundException();
        }
    }

//    /**
//     * @Route("/media/{id}/{name<[^/]+>}", name="_media_download_file", methods={"GET"})
//     */
    public function downloadFile(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $id = $request->attributes->getInt('id');
        $name = $request->attributes->get('name');

        $media = $this->entityManager->find(Media::class, $id);

        if (!$media instanceof Media) {
            throw $this->createNotFoundException();
        }

        if ($media->getName() !== $name) {
            throw $this->createNotFoundException();
        }

        try {
            return $this->downloadManager->downloadFile($media);
        } catch (CouldNotDownloadFile $e) {
            throw $this->createNotFoundException();
        }
    }

//    /**
//     * @Route("/_media/upload-file", name="_media_upload_file", methods={"POST"})
//     */
    public function uploadFile(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

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

        return $this->json(
            [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'url' => $this->downloadManager->generateDownloadUrlForFile($media),
            ]
        );
    }

//    /**
//     * @Route("/_media/upload-image", name="_media_upload_image", methods={"POST"})
//     */
    public function uploadImage(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

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

        return $this->json(
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
