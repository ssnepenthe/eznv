<?php

namespace Eznv;

use Closure;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

// @todo We should ensure all paths are configurable.
final class Environment
{
    private static ?Closure $environmentDirectoryResolver = null;

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

    public function isOrphaned(): bool
    {
        return $this->project instanceof Project && ! is_dir($this->project->path);
    }

    public static function fromDirectory(string $directory): self
    {
        $directory = realpath($directory);

        if (false === $directory) {
            throw new RuntimeException('@todo realpath');
        }

        $environment = new self($directory);

        if (! is_dir($environment->path)) {
            throw new RuntimeException('@todo env dir doesnt exist');
        }

        if (! file_exists($environment->getComposerJsonPath())) {
            throw new RuntimeException("Environment {$directory} has not been initialized");
        }

        $composer = Support::readJsonFile($environment->getComposerJsonPath());
        $project = $composer['extra']['eznv']['project'] ?? [];

        if (! is_array($project)) {
            throw new RuntimeException('@todo old style project metadata');
        }

        $environment->project = Project::fromArray($project);

        return $environment;
    }

    public static function fromProject(Project $project): self
    {
        // @todo realpath()? This is only used when first creating a project so directory doesn't need to exist.
        // maybe use Path class to normalize instead?
        return new self(self::resolveEnvironmentDirectory($project), $project);
    }

    public static function resolveEnvironmentDirectory(Project $project): string
    {
        if (! self::$environmentDirectoryResolver instanceof Closure) {
            throw new RuntimeException();
        }

        return (self::$environmentDirectoryResolver)($project);
    }

    public static function resolveEnvironmentDirectoryUsing(Closure $callback)
    {
        self::$environmentDirectoryResolver = $callback;
    }
}