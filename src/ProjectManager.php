<?php

namespace Eznv;

use RuntimeException;

final class ProjectManager
{
    public function createForCwd(): Project
    {
        // @todo strip /run/host to save us from ourselves when using distrobox?
        $directory = getcwd();

        // @todo can this ever be an empty string?
        if ('' === $directory || false === $directory) {
            throw new RuntimeException();
        }

        return $this->createForDirectory($directory);
    }

    public function createForDirectory(string $directory): Project
    {
        // @todo realpath()?
        if ('' === $directory) {
            return $this->createForCwd();
        }

        if (! is_dir($directory)) {
            throw new RuntimeException();
        }

        if (! file_exists($directory . '/composer.json')) {
            throw new RuntimeException();
        }

        $composerJson = Support::readJsonFile($directory . '/composer.json');

        // @todo Does composer even allow you to set an empty name and type?
        if (! isset($composerJson['name']) || '' === $composerJson['name']) {
            throw new RuntimeException();
        }

        if (isset($composerJson['type']) && '' === $composerJson['type']) {
            throw new RuntimeException();
        }

        return new Project($directory, $composerJson['name'], $composerJson['type'] ?? 'library');
    }

    public function createForEnvironment(Environment $environment): Project
    {
        if (null === $environment->projectPath) {
            throw new RuntimeException();
        }

        return $this->createForDirectory($environment->projectPath);
    }
}