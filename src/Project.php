<?php

namespace Eznv;

final readonly class Project
{
    public string $hash;
    public string $id;

    public function __construct(public string $path, public string $name, public string $type)
    {
        $this->hash = hash('sha256', $this->path);
        // @todo long-term we should probably implement some sort of collision detection.
        $this->id = substr($this->hash, 0, 12);
    }
}