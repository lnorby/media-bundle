<?php

namespace Lnorby\MediaBundle\Entity;

class Media
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string|null
     */
    private $path = null;

    /**
     * @var string|null
     */
    private $originalName = null;

    /**
     * @var string|null
     */
    private $mimeType = null;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function uploaded(string $path, string $originalName, string $mimeType): void
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function isImage(): bool
    {
        return strpos($this->mimeType, 'image/') === 0;
    }

    public function isUploaded(): bool
    {
        return null !== $this->path;
    }
}
