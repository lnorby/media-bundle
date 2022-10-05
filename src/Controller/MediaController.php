<?php

namespace Lnorby\MediaBundle\Controller;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\Repository\MediaRepository;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validation;

// TODO: translations
final class MediaController
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

        if (preg_match('#^((?:[0-9a-f]{2}/){3}[0-9a-f]{10})\.(\d+)x(\d+)([rc])(\.[a-z0-9]+)$#', $path, $matches)) {
            try {
                $media = $this->mediaRepository->getByPath($matches[1] . $matches[5]);
            } catch (CouldNotFindMedia $e) {
                throw new NotFoundHttpException();
            }

            return $this->downloadManager->downloadModifiedImage($media, $matches[2], $matches[3], $matches[4]);
        }

        throw new NotFoundHttpException();
    }

    public function uploadFile(Request $request): Response
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->files->get('file');

        $validator = Validation::createValidator();
        $violations = $validator->validate(
            $file,
            [
                new File([
                    'uploadIniSizeErrorMessage' => 'A fájlt nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadFormSizeErrorMessage' => 'A fájlt nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadNoFileErrorMessage' => 'Nem adott meg fájlt.',
                    'uploadErrorMessage' => 'A fájlt nem sikerült feltölteni. Kérjük, próbálja újra!',
                ])
            ]
        );

        if ($violations->count() > 0) {
            return $this->errorResponse($violations->get(0)->getMessage());
        }

        try {
            $media = $this->uploadManager->uploadFile(
                $file->getClientOriginalName(),
                $file->getContent(),
                $file->getMimeType()
            );
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
        /**
         * @var UploadedFile $image
         */
        $image = $request->files->get('image');
        $minWidth = $request->request->getInt('min_width');
        $minHeight = $request->request->getInt('min_height');

        $validator = Validation::createValidator();
        $violations = $validator->validate(
            $image,
            [
                new File([
                    'uploadIniSizeErrorMessage' => 'A képet nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadFormSizeErrorMessage' => 'A képet nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadNoFileErrorMessage' => 'Nem adott meg képet.',
                    'uploadErrorMessage' => 'A képet nem sikerült feltölteni. Kérjük, próbálja újra!',
                ]),
                new Image([
                    'minHeight' => $minHeight,
                    'minHeightMessage' => sprintf('A képnek legalább %d pixel magasságúnak kell lennie.', $minHeight),
                    'minWidth' => $minWidth,
                    'minWidthMessage' => sprintf('A képnek legalább %d pixel szélességűnek kell lennie.', $minWidth),
                    'mimeTypesMessage' => 'A megadott fájl nem kép.',
                    'sizeNotDetectedMessage' => 'A megadott fájl nem kép.',
                ]),
            ]
        );

        if ($violations->count() > 0) {
            return $this->errorResponse($violations->get(0)->getMessage());
        }

        try {
            $media = $this->uploadManager->uploadImage($image->getClientOriginalName(), $image->getContent());
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

    public function uploadImageViaEditor(Request $request): Response
    {
        /**
         * @var UploadedFile $image
         */
        $image = $request->files->get('upload');

        $validator = Validation::createValidator();
        $violations = $validator->validate(
            $image,
            [
                new File([
                    'uploadIniSizeErrorMessage' => 'A képet nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadFormSizeErrorMessage' => 'A képet nem sikerült feltölteni, mert túl nagy a mérete.',
                    'uploadNoFileErrorMessage' => 'Nem adott meg képet.',
                    'uploadErrorMessage' => 'A képet nem sikerült feltölteni. Kérjük, próbálja újra!',
                ]),
                new Image([
                    'mimeTypesMessage' => 'A megadott fájl nem kép.',
                    'sizeNotDetectedMessage' => 'A megadott fájl nem kép.',
                ]),
            ]
        );

        if ($violations->count() > 0) {
            return $this->errorResponse($violations->get(0)->getMessage());
        }

        try {
            $media = $this->uploadManager->uploadImage($image->getClientOriginalName(), $image->getContent());
        } catch (CouldNotUploadFile $e) {
            return $this->editorErrorResponse('A képet nem sikerült feltölteni. Kérjük, próbálja újra!');
        }

        return new JsonResponse(
            [
                'url' => $this->downloadManager->generateDownloadUrlForFile($media),
            ]
        );
    }

    private function errorResponse(string $message): Response
    {
        return new Response($message, 422);
    }

    private function editorErrorResponse(string $message): Response
    {
        return new JsonResponse(
            [
                'error' => [
                    'message' => $message,
                ]
            ]
        );
    }
}
