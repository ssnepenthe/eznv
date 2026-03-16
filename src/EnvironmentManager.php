<?php

namespace Eznv;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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
        // @todo realpath()?
        $environment = new Environment($directory);

        if (file_exists($environment->getComposerJsonPath())) {
            $composer = Support::readJsonFile($environment->getComposerJsonPath());
            $projectDirectory = $composer['extra']['eznv']['project'] ?? null;

            if (is_string($projectDirectory)) {
                // @todo I'm not sure there is a good reason to allow relative paths, but it makes testing a bit easier.
                $environment->project = $this->createProject(Path::makeAbsolute($projectDirectory, $directory));
            }
        }

        return $environment;
    }

    public function createForProject(Project $project)
    {
        $directory = $this->config->path($project->hash);

        // @todo realpath()?
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

    public function writeComposerJson(Environment $environment)
    {
        $require = [
            'ext-pdo' => '*',
            'ext-pdo_sqlite' => '*',
            'psy/psysh' => '*',
            'roots/wordpress' => '*',
            'wp-plugin/sqlite-database-integration' => '*',
            $environment->project->name => '*',
        ];

        if ('wordpress-theme' !== ($environment->project->type ?? 'library')) {
            $require['wp-theme/twentytwentyfive'] = '*';
        }

        $composerJson = [
            'name' => "eznv/{$environment->project->id}",
            'version' => '1.0.0',
            'license' => 'MIT',
            'require' => $require,
            'repositories' => [
                [
                    'name' => 'wp-composer',
                    'type' => 'composer',
                    'url' => 'https://repo.wp-composer.com',
                    'only' => [
                        'wp-plugin/*',
                        'wp-theme/*',
                    ],
                ],
                [
                    'type' => 'path',
                    'url' => $environment->project->path,
                ],
            ],
            'config' => [
                'allow-plugins' => [
                    'roots/wordpress-core-installer' => true,
                    'composer/installers' => true,
                ],
            ],
            'extra' => [
                'eznv' => [
                    'project' => $environment->project->path,
                ],
                'installer-paths' => [
                    'public/wp-content/mu-plugins/{$name}/' => ['type:wordpress-muplugin'],
                    'public/wp-content/plugins/{$name}/' => ['type:wordpress-plugin'],
                    'public/wp-content/themes/{$name}' => ['type:wordpress-theme'],
                ],
                'wordpress-install-dir' => 'public/wordpress',
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];

        (new Filesystem)->dumpFile(
            $environment->getComposerJsonPath(),
            json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}