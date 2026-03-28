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
        return ! $this->project instanceof Project || ! is_dir($this->project->path);
    }

    public static function fromDirectory(string $directory): self
    {
        $original = $directory;
        $directory = realpath($directory);

        if (false === $directory) {
            throw new RuntimeException("File {$original} does not exist");
        }

        $environment = new self($directory);

        if (! is_dir($environment->path)) {
            throw new RuntimeException("File {$directory} is not a directory");
        }

        if (! file_exists($environment->getComposerJsonPath())) {
            throw new RuntimeException("Environment {$directory} has not been initialized");
        }

        $composer = Container::instance()->get(Filesystem::class)->readJsonFile($environment->getComposerJsonPath());
        $project = $composer['extra']['eznv']['project'] ?? [];

        if (! is_array($project)) {
            throw new RuntimeException("Environment at {$directory} was created with an older version of eznv - did you run `eznv post-update`?");
        }

        if (! array_key_exists('path', $project)) {
            throw new RuntimeException("Environment at {$directory} contains invalid project metadata");
        }

        // Not really necessary but makes testing a bit simpler.
        $project['path'] = Path::makeAbsolute($project['path'], $directory);

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
            throw new RuntimeException('Environment directory resolver not set - call ' . __CLASS__ . '::resolveEnvironmentDirectoryUsing()');
        }

        return (self::$environmentDirectoryResolver)($project);
    }

    public static function resolveEnvironmentDirectoryUsing(?Closure $callback)
    {
        self::$environmentDirectoryResolver = $callback;
    }
}