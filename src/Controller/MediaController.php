<?php

namespace Lnorby\MediaBundle\Controller;

use Lnorby\MediaBundle\DownloadManager;
use Lnorby\MediaBundle\Exception\CouldNotUploadFile;
use Lnorby\MediaBundle\UploadManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(UploadManager $uploadManager, DownloadManager $downloadManager, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        $this->uploadManager = $uploadManager;
        $this->downloadManager = $downloadManager;
        $this->translator = $translator;
        $this->validator = $validator;
    }

    public function download(Request $request): Response
    {
        $publicPath = $request->attributes->get('path');

        try {
            $realPath = $this->downloadManager->getRealPathFromPublicPath($publicPath);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($realPath, 200, [], false);
    }

    public function uploadFile(Request $request): Response
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->files->get('file');
        $violations = $this->validator->validate($file, [new File()]);

        if ($violations->count() > 0) {
            return $this->errorResponse($violations->get(0)->getMessage(), $violations->get(0)->getParameters());
        }

        try {
            $media = $this->uploadManager->uploadFileAndCreateMedia(
                $file->getClientOriginalName(),
                $file->getContent(),
                $file->getMimeType()
            );
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('The file could not be uploaded.');
        }

        return new JsonResponse(
            [
                'id' => $media->id(),
                'name' => $media->name(),
                'url' => $this->downloadManager->downloadUrlForMediaFile($media),
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
            return $this->errorResponse($violations->get(0)->getMessage(), $violations->get(0)->getParameters());
        }

        try {
            $media = $this->uploadManager->uploadImageAndCreateMedia(
                $image->getClientOriginalName(),
                $image->getContent()
            );
        } catch (CouldNotUploadFile $e) {
            return $this->errorResponse('The file could not be uploaded.');
        }

        return new JsonResponse(
            [
                'id' => $media->id(),
                'url' => $this->downloadManager->downloadUrlForMediaModifiedImage(
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
        $violations = $this->validator->validate($image, [new File(), new Image()]);

        if ($violations->count() > 0) {
            return $this->editorErrorResponse(
                $violations->get(0)->getMessage(),
                $violations->get(0)->getParameters()
            );
        }

        try {
            $media = $this->uploadManager->uploadImageAndCreateMedia(
                $image->getClientOriginalName(),
                $image->getContent()
            );
        } catch (CouldNotUploadFile $e) {
            return $this->editorErrorResponse('The file could not be uploaded.');
        }

        return new JsonResponse(
            [
                'url' => $this->downloadManager->downloadUrlForMediaFile($media, false),
            ]
        );
    }

    private function errorResponse(string $message, array $params = []): Response
    {
        return new Response($this->translator->trans($message, $params), 422);
    }

    private function editorErrorResponse(string $message, array $params = []): Response
    {
        return new JsonResponse(
            [
                'error' => [
                    'message' => $this->translator->trans($message, $params),
                ]
            ]
        );
    }
}
