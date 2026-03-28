<?php

namespace Eznv;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class Filesystem
{
    private SymfonyFilesystem $fs;

    public function __construct(?SymfonyFilesystem $fs = null)
    {
        $this->fs = $fs ?? new SymfonyFilesystem();
    }

    public function ensureDirectoryExists(string $directory)
    {
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory)) {
            throw new RuntimeException("File already exists at path {$directory}");
        }
    }

    public function readJsonFile(string $path)
    {
        if (! file_exists($path)) {
            throw new RuntimeException("No file exists at {$path}");
        }

        $contents = $this->fs->readFile($path);
        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    public function moveDirectory(string $origin, string $target)
    {
        // @todo validation
        $this->fs->rename($origin, $target);
    }

    public function removeDirectory(string $directory)
    {
        // @todo validation
        $this->fs->remove($directory);
    }

    public function writeJsonToFile(array $json, string $path)
    {
        $this->fs->dumpFile(
            $path,
            json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}