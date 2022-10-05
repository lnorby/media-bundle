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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(UploadManager $uploadManager, DownloadManager $downloadManager, MediaRepository $mediaRepository, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        $this->uploadManager = $uploadManager;
        $this->downloadManager = $downloadManager;
        $this->mediaRepository = $mediaRepository;
        $this->translator = $translator;
        $this->validator = $validator;
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
        $locale = $request->request->get('locale', 'hu');

        $violations = $this->validator->validate($file, [new File()]);

        if ($violations->count() > 0) {
            return $this->errorResponse(
                $violations->get(0)->getMessage(),
                $violations->get(0)->getParameters(),
                $locale
            );
        }

        try {
            $media = $this->uploadManager->uploadFile(
                $file->getClientOriginalName(),
                $file->getContent(),
                $file->getMimeType()
            );
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('The file could not be uploaded.', [], $locale);
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
        $locale = $request->request->get('locale', 'hu');

        $violations = $this->validator->validate(
            $image,
            [
                new File(),
                new Image([
                    'minHeight' => $minHeight,
                    'minWidth' => $minWidth,
                ]),
            ]
        );

        if ($violations->count() > 0) {
            return $this->errorResponse(
                $violations->get(0)->getMessage(),
                $violations->get(0)->getParameters(),
                $locale
            );
        }

        try {
            $media = $this->uploadManager->uploadImage($image->getClientOriginalName(), $image->getContent());
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('The file could not be uploaded.', [], $locale);
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
        $locale = $request->request->get('locale', 'hu');

        $violations = $this->validator->validate($image, [new File(), new Image()]);

        if ($violations->count() > 0) {
            return $this->editorErrorResponse(
                $violations->get(0)->getMessage(),
                $violations->get(0)->getParameters(),
                $locale
            );
        }

        try {
            $media = $this->uploadManager->uploadImage($image->getClientOriginalName(), $image->getContent());
        } catch (CouldNotUploadFile $e) {
            return $this->editorErrorResponse('The file could not be uploaded.', [], $locale);
        }

        return new JsonResponse(
            [
                'url' => $this->downloadManager->generateDownloadUrlForFile($media),
            ]
        );
    }

    private function errorResponse(string $message, array $params, string $locale): Response
    {
        return new Response($this->translator->trans($message, $params, null, $locale), 422);
    }

    private function editorErrorResponse(string $message, array $params, string $locale): Response
    {
        return new JsonResponse(
            [
                'error' => [
                    'message' => $this->translator->trans($message, $params, null, $locale),
                ]
            ]
        );
    }
}
