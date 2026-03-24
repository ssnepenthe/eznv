<?php

namespace Eznv;

use Closure;
use RuntimeException;

final class EnvironmentFinder
{
    private Eznv $config;
    private EnvironmentManager $manager;

    public function __construct(?Eznv $config = null)
    {
        $this->config = $config ?? Eznv::instance();
        $this->manager = new EnvironmentManager();
    }

    // @todo Do we actually want a nullable return or should we throw?
    public function find(string $identifier): ?Environment
    {
        return match (true) {
            str_starts_with($identifier, '/') => $this->findByProjectDirectory($identifier),
            str_contains($identifier, '/') => $this->findByProjectName($identifier),
            12 === strlen($identifier) => $this->findByProjectId($identifier),
            default => $this->findByProjectHash($identifier),
        };
    }

    public function findAll()
    {
        $environments = [];

        foreach (scandir($this->config->baseDirectory) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $fullpath = $this->config->path($file);

            if (! is_dir($fullpath)) {
                continue;
            }

            $environments[] = $this->manager->create($fullpath);
        }

        return $environments;
    }

    public function findByProjectDirectory(string $directory): Environment
    {
        return $this->findBy(fn (Environment $environment) => $directory === $environment->project->path, $directory);
    }

    public function findByProjectName(string $name): Environment
    {
        return $this->findBy(fn (Environment $environment) => $name === $environment->project->name, $name);
    }

    public function findByProjectId(string $id): Environment
    {
        return $this->findBy(fn (Environment $environment) => $id === $environment->project->id, $id);
    }

    public function findByProjectHash(string $hash): Environment
    {
        return $this->findBy(fn (Environment $environment) => $hash === $environment->project->hash, $hash);
    }

    private function findBy(Closure $callback, string $identifier): Environment
    {
        $found = array_find($this->findAll(), $callback);

        if (! $found instanceof Environment) {
            throw new RuntimeException("Unable to find environment {$identifier}");
        }

        return $found;
    }
}