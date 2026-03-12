<?php

namespace Eznv;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class Support
{
    public static function ensureDirectoryExists(string $directory)
    {
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory)) {
            throw new RuntimeException("File already exists at path {$directory}");
        }
    }

    /**
     * @return string|false
     */
    public static function getEnv(string $name)
    {
        if (array_key_exists($name, $_SERVER)) {
            return (string) $_SERVER[$name];
        }

        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }

        return getenv($name);
    }

    public static function makePathRelativeToHome(string $path): string
    {
        $home = Support::getEnv('HOME');

        if (! $home) {
            return $path;
        }

        if (! str_starts_with($path, $home)) {
            return $path;
        }

        $relative = (new Filesystem)->makePathRelative($path, $home);

        if ($relative === $path) {
            return $path;
        }

        return "~/{$relative}";
    }

    public static function readJsonFile(string $path): array
    {
        if (! file_exists($path)) {
            throw new RuntimeException();
        }

        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new RuntimeException("Failed to read file at {$path}");
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}