<?php

namespace Lnorby\MediaBundle\Exception;

final class CouldNotFindMedia extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Could not find media with id "%d".', $id));
    }

    public static function withPath(string $path): self
    {
        return new self(sprintf('Could not find media with path "%s".', $path));
    }
}
