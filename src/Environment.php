<?php

namespace Eznv;

use RuntimeException;

final class Environment
{
    private static string $baseDirectory = '';
    public readonly string $path;

    public function __construct(public Project $project)
    {
        // @todo I don't love that we are creating directories in the constructor...
        if (! file_exists(self::getBaseDirectory())) {
            mkdir(self::getBaseDirectory(), 0755, true);
        }

        if (! is_dir(self::getBaseDirectory())) {
            throw new RuntimeException('File already exists at path ' . self::getBaseDirectory());
        }

        $this->path = self::getBaseDirectory() . "/{$this->project->hash}";

        if (! file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }

        if (! is_dir($this->path)) {
            throw new RuntimeException("File already exists at path {$this->path}");
        }
    }

    public function writeComposerJson(): void
    {
        $written = file_put_contents("{$this->path}/composer.json", json_encode(
            $this->generateComposerJsonArray(),
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        if (false === $written) {
            throw new RuntimeException("Failed to write composer.json in {$this->path}");
        }
    }

    public function writeWpCliYml(): void
    {
        $written = file_put_contents("{$this->path}/wp-cli.yml", "path: wordpress\n");

        if (false === $written) {
            throw new RuntimeException("Failed to write wp-cli.yml in {$this->path}");
        }
    }

    public function getDatabasePath(): string
    {
        // @todo Should this be configurable? User can already override in wp-config.php but it would probably be
        // preferable to have wp-config.php read from config we store in composer.json extra field.
        return "{$this->path}/wordpress/wp-content/database/.ht.sqlite";
    }

    public function getInstalledVersion(string $package): ?string
    {
        $installedPath = "{$this->path}/vendor/composer/installed.php";

        if (! file_exists($installedPath)) {
            return null;
        }

        $installed = require $installedPath;

        return $installed['versions'][$package]['pretty_version'] ?? null;
    }

    public function isInitialized(): bool
    {
        return file_exists("{$this->path}/composer.json");
    }

    public static function fromHash(string $hash): self
    {
        // @todo Get back to this when we have a better idea of what the "list" command looks like...
        $path = self::getBaseDirectory() . "/{$hash}";
        $composerFile = "{$path}/composer.json";
        $contents = file_get_contents($composerFile);
        $json = json_decode($contents, true);
        $project = $json['extra']['eznv']['project'] ?? '';

        if ('' === $project) {
            throw new RuntimeException();
        }

        $project = new Project($project);

        return $project->environment();
    }

    public static function getBaseDirectory()
    {
        if ('' === self::$baseDirectory) {
            $xdgDataHome = $_ENV['XDG_DATA_HOME'] ?? $_SERVER['XDG_DATA_HOME'] ?? null;

            if ($xdgDataHome && is_dir($xdgDataHome)) {
                $dir = $xdgDataHome . '/eznv';
            } else {
                $home = $_ENV['HOME'] ?? $_SERVER['HOME'] ?? null;

                if (! $home) {
                    throw new \RuntimeException('Could not determine home directory. Please set HOME or XDG_DATA_HOME environment variable.');
                }

                $dir = $home . '/.eznv';
            }

            self::$baseDirectory = $dir;
        }

        return self::$baseDirectory;
    }

    private function generateComposerJsonArray(): array
    {
        $require = [
            'ext-pdo' => '*',
            'ext-pdo_sqlite' => '*',
            'psy/psysh' => '*',
            'roots/wordpress' => '*',
            'wpackagist-plugin/sqlite-database-integration' => '*',
            $this->project->name => '*',
        ];

        if ('wordpress-theme' !== ($this->project->type ?? 'library')) {
            $require['wpackagist-theme/twentytwentyfive'] = '*';
        }

        return [
            'name' => "eznv/{$this->project->hash}",
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
                    'url' => $this->project->path,
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
                    'project' => $this->project->path,
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
    }
}
