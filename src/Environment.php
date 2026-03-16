<?php

namespace Eznv;

use Symfony\Component\Filesystem\Path;

// @todo We should ensure all paths are configurable.
final class Environment
{
    public function __construct(public readonly string $path, public ?Project $project = null)
    {}

    public function getComposerJsonPath(): string
    {
        return $this->getPath('composer.json');
    }

    public function getContentPath(string ...$pathParts): string
    {
        return $this->getPublicPath('wp-content', ...$pathParts);
    }

    public function getDatabaseDropinPath(): string
    {
        return $this->getContentPath('db.php');
    }

    public function getDatabasePath(): string
    {
        return $this->getContentPath('database', '.ht.sqlite');
    }

    public function getDebugLogPath(): string
    {
        return $this->getContentPath('debug.log');
    }

    public function getPath(string ...$pathParts): string
    {
        return Path::join($this->path, ...$pathParts);
    }

    public function getPublicPath(string ...$pathParts): string
    {
        return $this->getPath('public', ...$pathParts);
    }

    public function getWordPressPath(string ...$pathParts): string
    {
        return $this->getPublicPath('wordpress', ...$pathParts);
    }

    public function getWpCliYmlPath(): string
    {
        return $this->getPath('wp-cli.yml');
    }

    public function getWpConfigPath(): string
    {
        return $this->getPublicPath('wp-config.php');
    }

    public function isInitialized(): bool
    {
        return is_dir($this->path) && file_exists($this->getComposerJsonPath());
    }
}