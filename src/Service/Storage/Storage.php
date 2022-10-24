<?php

namespace Lnorby\MediaBundle\Service\Storage;

interface Storage
{
    /**
     * @throws \RuntimeException
     */
    public function createFile(string $file, $content): void;

    public function deleteFile(string $file): void;

    public function fileExists(string $file): bool;

    public function getRealPath(string $file): string;

    public function search(string $pattern): array;
}
