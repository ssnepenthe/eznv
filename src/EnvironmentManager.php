<?php

namespace Eznv;

use RuntimeException;

// @todo Better name?
final class EnvironmentManager
{
    private Eznv $config;

    public function __construct(?Eznv $config = null)
    {
        $this->config = $config ?? Eznv::instance();
    }

    public function create(string $directory)
    {
        $directory = realpath($directory);

        if (false === $directory) {
            throw new RuntimeException('@todo realpath');
        }

        $environment = new Environment($directory);

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

    public function createForProject(Project $project)
    {
        $directory = $this->config->path($project->hash);

        // @todo realpath()? This is only used when first creating a project so directory doesn't need to exist.
        // maybe use Path class to normalize instead?
        $environment = new Environment($directory, $project);

        return $environment;
    }

    public function createProject(string $directory): Project
    {
        // @todo realpath()?
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
}