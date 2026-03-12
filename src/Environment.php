<?php

namespace Eznv;

// @todo We should ensure all paths are configurable.
final class Environment
{
    public function __construct(public readonly string $path, public ?string $projectPath = null)
    {}

    public function getComposerJsonPath(): string
    {
        return $this->getPath('composer.json');
    }

    public function getDatabaseDropinPath(): string
    {
        return $this->getWordPressPath('wp-content/db.php');
    }

    public function getDatabasePath(): string
    {
        return $this->getWordPressPath('wp-content/database/.ht.sqlite');
    }

    public function getPath(string ...$pathParts): string
    {
        $pathParts = array_map(fn ($part) => trim($part, '/\\'), array_filter($pathParts));
        $path = implode('/', $pathParts);

        // @todo trim $this->path?
        return "{$this->path}/{$path}";
    }

    public function getWordPressPath(string $path = ''): string
    {
        return $this->getPath('wordpress', $path);
    }

    public function getWpCliYmlPath(): string
    {
        return $this->getPath('wp-cli.yml');
    }

    public function getWpConfigPath(): string
    {
        return $this->getWordPressPath('wp-config.php');
    }

    public function getInstalledPackageVersion(string $package): ?string
    {
        // @todo I think we need a minimum composer runtime version to get installed.php.
        $installedPath = "{$this->path}/vendor/composer/installed.php";

        if (! file_exists($installedPath)) {
            return null;
        }

        $installed = require $installedPath;

        return $installed['versions'][$package]['pretty_version'] ?? null;
    }

    public function isInitialized(): bool
    {
        return is_dir($this->path) && file_exists($this->getComposerJsonPath());
    }
}