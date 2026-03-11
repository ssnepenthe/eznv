<?php

namespace Eznv;

use Symfony\Component\Filesystem\Filesystem;

final class Support
{
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
}