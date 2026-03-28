<?php

namespace Eznv\Tests;

use Eznv\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// @todo Decide on approach to testing write operations - virtual filesystem or just write to tmp?
class FilesystemTest extends TestCase
{
    public function testEnsureDirectoryExistsThrowsWhenPathIsFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File already exists at path');

        $fs = new Filesystem();
        $fs->ensureDirectoryExists(__DIR__ . '/fixtures/just-a-file');
    }

    public function testReadJsonFileThrowsWhenFileDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No file exists at');

        $fs = new Filesystem();
        $fs->readJsonFile(__DIR__ . '/fixtures/uninitialized-environment/composer.json');
    }

    public function testReadJsonFile()
    {
        $fs = new Filesystem();
        $json = $fs->readJsonFile(__DIR__ . '/fixtures/initialized-environment/composer.json');

        $this->assertSame('eznv/initialized-environment', $json['name']);
    }
}