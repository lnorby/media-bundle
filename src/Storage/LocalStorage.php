<?php

namespace Lnorby\MediaBundle\Storage;

final class LocalStorage implements Storage
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function createFile(string $file, $content): void
    {
        if ('' === $file) {
            throw new \InvalidArgumentException('No file specified.');
        }

        if ($this->fileExists($file)) {
            throw new \RuntimeException('File already exists.');
        }

        $this->makeDirectoryIfNotExists(pathinfo($file, PATHINFO_DIRNAME));

        if (!@file_put_contents($this->getRealPath($file), $content)) {
            throw new \RuntimeException('Cannot create file.');
        }
    }

    public function deleteFile(string $file): void
    {
        if ('' === $file) {
            throw new \InvalidArgumentException('No file specified.');
        }

        @unlink($this->getRealPath($file));
    }

    public function fileExists(string $file): bool
    {
        return file_exists($this->getRealPath($file));
    }

    public function getRealPath(string $file): string
    {
        return $this->path . '/' . $file;
    }

    public function search(string $pattern): array
    {
        return array_map(
            function ($path) {
                return str_replace($this->path . '/', '', $path);
            },
            glob($this->getRealPath($pattern))
        );
    }

    private function makeDirectoryIfNotExists(string $name): void
    {
        $path = $this->getRealPath($name);

        if (is_dir($path)) {
            return;
        }

        if (!@mkdir($path, 0777, true)) {
            throw new \RuntimeException('Cannot create directory.');
        }
    }
}
