<?php

namespace Eznv;

use RuntimeException;

final class Eznv
{
    public readonly string $baseDirectory;
    private static ?self $instance = null;

    private function __construct()
    {
        $xdgDataHome = Support::getEnv('XDG_DATA_HOME');

        if ($xdgDataHome && is_dir($xdgDataHome)) {
            $dir = $xdgDataHome . '/eznv';
        } else {
            $home = Support::getEnv('HOME');

            if (! $home) {
                throw new RuntimeException('Could not determine home directory. Please set HOME or XDG_DATA_HOME environment variable.');
            }

            $dir = $home . '/.eznv';
        }

        $this->baseDirectory = $dir;
    }

    public function path(string ...$pathParts): string
    {
        $pathParts = array_map(fn ($part) => trim($part, '/\\'), array_filter($pathParts));
        $path = implode('/', $pathParts);

        return "{$this->baseDirectory}/{$path}";
    }

    public static function instance()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}