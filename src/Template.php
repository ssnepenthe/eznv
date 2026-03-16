<?php

namespace Eznv;

use Symfony\Component\Filesystem\Filesystem;

final class Template
{
    public static function render(string $template, array $data = []): string
    {
        $keys = array_map(fn (string $key) => "{{$key}}", array_keys($data));

        return str_replace($keys, array_values($data), (new Filesystem)->readFile($template));
    }

    public static function write(string $template, string $path, array $data = []): void
    {
        (new Filesystem)->dumpFile($path, self::render($template, $data));
    }
}