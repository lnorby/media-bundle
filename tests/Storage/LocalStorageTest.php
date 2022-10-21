<?php

namespace Lnorby\MediaBundle\Tests\Storage;

use Lnorby\MediaBundle\Storage\LocalStorage;

class LocalStorageTest extends StorageTest
{
    protected function setUp(): void
    {
        $this->storageInstance = new LocalStorage('');
    }
}
