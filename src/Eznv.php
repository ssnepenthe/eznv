<?php

namespace Eznv;

use Generator;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

final class Eznv
{
    public readonly int $version;
    public int $installedVersion;

    public readonly string $baseDirectory;

    public function __construct(private Filesystem $fs)
    {
        $xdgDataHome = Support::getEnv('XDG_DATA_HOME');

        if ($xdgDataHome && is_dir($xdgDataHome)) {
            $dir = $xdgDataHome . '/eznv';
        } else {
            $home = Support::getEnv('HOME');

            if (! $home) {
                throw new RuntimeException('Could not determine home directory. Please set HOME or XDG_DATA_HOME environment variable.');
            }

            $dir = $home . '/.eznv';
        }

        $this->baseDirectory = $dir;

        $this->version = 1;
        $this->readEznvJson();
    }

    public function environmentPaths(): Generator
    {
        $environments = scandir($this->baseDirectory);

        if (false === $environments) {
            throw new RuntimeException();
        }

        foreach ($environments as $directory) {
            if ('.' === $directory || '..' === $directory) {
                continue;
            }

            $path = $this->path($directory);

            if (! is_dir($path)) {
                continue;
            }

            yield $path;
        }
    }

    public function flushEznvJson()
    {
        $this->fs->writeJsonToFile(['version' => $this->installedVersion], $this->path('eznv.json'));
    }

    public function isUpdateRequired(): bool
    {
        return $this->installedVersion < $this->version;
    }

    public function path(string ...$pathParts): string
    {
        return Path::join($this->baseDirectory, ...$pathParts);
    }

    private function readEznvJson()
    {
        $metadataFile = $this->path('eznv.json');
        $installedVersion = 0;

        if (file_exists($metadataFile)) {
            $metadata = $this->fs->readJsonFile($metadataFile);

            if (array_key_exists('version', $metadata) && is_int($metadata['version'])) {
                $installedVersion = $metadata['version'];
            }
        }

        $this->installedVersion = $installedVersion;
    }
}