<?php

namespace Eznv;

final class Template
{
    public static function render(string $template, array $data = []): string
    {
        $keys = array_map(fn (string $key) => "{{$key}}", array_keys($data));

        return str_replace($keys, array_values($data), file_get_contents($template));
    }

    public static function write(string $template, string $path, array $data = []): void
    {
        file_put_contents($path, self::render($template, $data));
    }
}