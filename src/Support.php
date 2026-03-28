<?php

namespace Eznv;

use RuntimeException;
use Symfony\Component\Filesystem\Path;

final class Support
{
    public static function getCwd(): string
    {
        $directory = getcwd();

        if (false === $directory) {
            throw new RuntimeException('Unable to determine project directory');
        }

        return $directory;
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

        $relative = Path::makeRelative($path, $home);

        if ($relative === $path) {
            return $path;
        }

        return "~/{$relative}";
    }
}