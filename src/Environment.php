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

    public function getDatabaseDropinPath(): string
    {
        return $this->getWordPressPath('wp-content/db.php');
    }

    public function getDatabasePath(): string
    {
        return $this->getWordPressPath('wp-content/database/.ht.sqlite');
    }

    public function getDebugLogPath(): string
    {
        return $this->getWordPressPath('wp-content/debug.log');
    }

    public function getPath(string ...$pathParts): string
    {
        return Path::join($this->path, ...$pathParts);
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

    public function isInitialized(): bool
    {
        return is_dir($this->path) && file_exists($this->getComposerJsonPath());
    }
}