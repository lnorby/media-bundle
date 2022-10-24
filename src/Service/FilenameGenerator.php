<?php

namespace Lnorby\MediaBundle\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

final class FilenameGenerator
{
    /**
     * @var SluggerInterface
     */
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function generateUniqueFilenameWithPath(string $extension): string
    {
        $uniqueFilename = bin2hex(random_bytes(8));

        return sprintf(
            '%s/%s/%s/%s.%s',
            substr($uniqueFilename, 0, 2),
            substr($uniqueFilename, 2, 2),
            substr($uniqueFilename, 4, 2),
            substr($uniqueFilename, 6),
            $extension
        );
    }

    public function convertToSafeFilename(string $originalFilename, string $extension): string
    {
        return sprintf(
            '%s.%s',
            strtolower($this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME))),
            $extension
        );
    }
}
