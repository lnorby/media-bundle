<?php

namespace Lnorby\MediaBundle\Entity;

class Media
{
    private int $id;
    private string $path;
    private string $name;
    private string $mimeType;

    public function __construct(string $path, string $name, string $mimeType)
    {
        $this->path = $path;
        $this->name = $name;
        $this->mimeType = $mimeType;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }
}
