<?php

namespace Eznv;

use RuntimeException;

final readonly class Project
{
    public string $hash;
    public string $id;

    public function __construct(
        public string $path,
        public string $name,
        public string $type,
        ?string $hash = null,
        ?string $id = null
    ) {
        $this->hash = $hash ?? hash('sha256', $this->path);
        // @todo long-term we should probably implement some sort of collision detection.
        $this->id = $id ?? substr($this->hash, 0, 12);
    }

    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'type' => $this->type,
        ];
    }

    public static function fromArray(array $array): self
    {
        if (! (
            array_key_exists('path', $array)
            && array_key_exists('name', $array)
            && array_key_exists('type', $array)
        )) {
            throw new RuntimeException();
        }

        $array['hash'] ??= null;
        $array['id'] ??= null;

        return new self(...$array);
    }
}