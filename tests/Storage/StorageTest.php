<?php

namespace Lnorby\MediaBundle\Tests\Storage;

use PHPUnit\Framework\TestCase;

abstract class StorageTest extends TestCase
{
    protected $storageInstance;

    public function testCreateFile(): void
    {
    }

    public function testDeleteFile(): void
    {
    }

    public function testFileExists(): void
    {
    }

    public function testGetRealPath(): void
    {
    }

    public function testDirectoryIfNotExists(): void
    {
    }

    protected function tearDown(): void
    {
        unset($this->storageInstance);
    }
}
