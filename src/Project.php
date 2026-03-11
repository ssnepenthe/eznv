<?php

namespace Eznv;

use RuntimeException;

final class Project
{
    public readonly string $hash;
    public readonly string $name;
    public readonly string $path;
    public readonly string $type;

    public function __construct(string $path)
    {
        $this->path = realpath($path);

        if (false === $this->path) {
            throw new RuntimeException("Project path does not exist: {$path}");
        }

        if (! is_dir($this->path)) {
            throw new RuntimeException("Project path is not a directory: {$path}");
        }

        $this->hash = hash('sha256', $this->path);

        $this->loadComposerJson();
    }

    public function environment(): Environment
    {
        return new Environment($this);
    }

    private function loadComposerJson(): void
    {
        $composerJson = $this->path . '/composer.json';

        if (! file_exists($composerJson)) {
            throw new RuntimeException("No composer.json found in project path: {$this->path}");
        }

        $contents = file_get_contents($composerJson);

        if (false === $contents) {
            throw new RuntimeException("Failed to read composer.json in project path: {$this->path}");
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! isset($decoded['name'])) {
            throw new RuntimeException("composer.json in project path must have a name field: {$this->path}");
        }

        $this->name = $decoded['name'];
        $this->type = $decoded['type'] ?? 'library';
    }
}
