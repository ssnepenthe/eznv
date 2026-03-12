<?php

namespace Eznv;

use RuntimeException;

// @todo Better name.
// @todo This whole class could probably be static... Same for project manager.
// @todo createForProjectDirectory() method?
// @todo createForProjectId() method?
final class EnvironmentManager
{
    private static string $baseDirectory = '';

    public function createForDirectory(string $directory, bool $validate = false): Environment
    {
        // @todo realpath()?
        $environment = new Environment($directory);

        if (file_exists($environment->getComposerJsonPath())) {
            $composer = Support::readJsonFile($environment->getComposerJsonPath());

            $environment->projectPath = $composer['extra']['eznv']['project'] ?? null;
        }

        // @todo We might not actually need this... We are manually ensuring directory exists in init command.
        if ($validate) {
            Support::ensureDirectoryExists($environment->path);
        }

        return $environment;
    }

    public function createForProject(Project $project, bool $validate = false)
    {
        $directory = $this->path($project->hash);

        return $this->createForDirectory($directory, $validate);
    }

    public function createForProjectHash(string $id, bool $validate = false)
    {
        $directory = $this->path($id);

        return $this->createForDirectory($directory, $validate);
    }

    public function writeComposerJson(Environment $environment, Project $project)
    {
        $require = [
            'ext-pdo' => '*',
            'ext-pdo_sqlite' => '*',
            'psy/psysh' => '*',
            'roots/wordpress' => '*',
            'wpackagist-plugin/sqlite-database-integration' => '*',
            $project->name => '*',
        ];

        if ('wordpress-theme' !== ($project->type ?? 'library')) {
            $require['wpackagist-theme/twentytwentyfive'] = '*';
        }

        $composerJson = [
            'name' => "eznv/{$project->id}",
            'version' => '1.0.0',
            'license' => 'MIT',
            'require' => $require,
            'repositories' => [
                [
                    'name' => 'wpackagist',
                    'type' => 'composer',
                    'url' => 'https://wpackagist.org',
                ],
                [
                    'type' => 'path',
                    'url' => $project->path,
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
                    'project' => $project->path,
                ],
                'installer-paths' => [
                    'wordpress/wp-content/mu-plugins/{$name}/' => ['type:wordpress-muplugin'],
                    'wordpress/wp-content/plugins/{$name}/' => ['type:wordpress-plugin'],
                    'wordpress/wp-content/themes/{$name}' => ['type:wordpress-theme'],
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];

        $written = file_put_contents(
            $environment->getComposerJsonPath(),
            json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if (false === $written) {
            throw new RuntimeException("Failed to write composer.json in {$environment->path}");
        }
    }

    public function writeWpCliYml(Environment $environment)
    {
        $written = file_put_contents($environment->getWpCliYmlPath(), "path: wordpress\n");

        if (false === $written) {
            throw new RuntimeException("Failed to write wp-cli.yml in {$environment->path}");
        }
    }

    public static function getBaseDirectory()
    {
        if ('' === self::$baseDirectory) {
            $xdgDataHome = Support::getEnv('XDG_DATA_HOME');

            if ($xdgDataHome && is_dir($xdgDataHome)) {
                $dir = $xdgDataHome . '/eznv';
            } else {
                $home = Support::getEnv('HOME');

                if (! $home) {
                    throw new \RuntimeException('Could not determine home directory. Please set HOME or XDG_DATA_HOME environment variable.');
                }

                $dir = $home . '/.eznv';
            }

            self::$baseDirectory = $dir;
        }

        return self::$baseDirectory;
    }

    public static function path(string ...$pathParts): string
    {
        $pathParts = array_map(fn ($part) => trim($part, '/\\'), array_filter($pathParts));
        $path = implode('/', $pathParts);

        return self::getBaseDirectory() . "/{$path}";
    }
}