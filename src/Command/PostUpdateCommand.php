<?php

namespace Eznv\Command;

use Eznv\Eznv;
use Eznv\Support;
use Generator;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'post-update',
    description: 'Run this command to update existing environments to be compatible with the installed eznv version.',
    hidden: true,
)]
final class PostUpdateCommand
{
    private const int FROM = 0;
    private const int TO = 1;

    public function __construct(private Eznv $config)
    {}

    public function __invoke(SymfonyStyle $io, #[Option] bool $dryRun = false): int
    {
        if (! ($this->config->installedVersion === self::FROM && $this->config->version >= self::TO)) {
            $io->writeln('Updates have already been applied.');

            return Command::SUCCESS;
        }

        if (! $dryRun) {
            $io->caution([
                'This operation can be destructive.',
                'It might be beneficial to do a dry-run first to see what changes will be made.',
            ]);

            $dryRun = $io->confirm('Do you want to do a dry run?', false);
        }

        $operations = [];

        foreach ($this->environments() as $directory) {
            $environmentPath = $this->config->path($directory);
            $composerJsonPath = $this->config->path($directory, 'composer.json');

            if (! file_exists($composerJsonPath)) {
                $operations[] = $this->deleteOperation($environmentPath, 'Invalid eznv environment - unmanaged files are not allowed in eznv directory.');

                continue;
            }

            $composerJson = Support::readJsonFile($composerJsonPath);
            $project = $composerJson['extra']['eznv']['project'] ?? null;

            // v0 environment.
            if (is_string($project)) {
                if ($this->isValidProjectDirectory($project)) {
                    $newProject = $this->createProjectArrayFromDirectory($project);
                    $composerJson['extra']['eznv']['project'] = $newProject;

                    $operations[] = $this->updateJsonFileOperation($composerJsonPath, $composerJson, 'project', $project, $newProject);

                    continue;
                } else {
                    $operations[] = $this->deleteOperation($environmentPath, 'Associated with an invalid project - environment considered orphaned.');

                    continue;
                }
            }

            // v1 environment. This might happen because the init command is allowed to bypass the update requirement.
            elseif (is_array($project)) {
                if ($this->isValidProjectArray($project)) {
                    $operations[] = $this->nullOperation($environmentPath);

                    continue;
                }

                if (array_key_exists('path', $project)) {
                    if ($this->isValidProjectDirectory($project['path'])) {
                        $projectArray = $this->createProjectArrayFromDirectory($project['path']);
                        $composerJson['extra']['eznv']['project'] = $projectArray;

                        $operations[] = $this->updateJsonFileOperation($composerJsonPath, $composerJson, 'project', $project, $projectArray);

                        continue;
                    } else {
                        $operations[] = $this->deleteOperation($environmentPath, 'Associated with an invalid project - environment considered orphaned.');

                        continue;
                    }
                } else {
                    $operations[] = $this->deleteOperation($environmentPath, 'Invalid eznv environment - unmanaged files are not allowed in eznv directory.');

                    continue;
                }
            }

            // User may have added their own files to our base directory... This is no longer allowed.
            else {
                $operations[] = $this->deleteOperation($environmentPath, 'Invalid eznv environment - unmanaged files are not allowed in eznv directory.');

                continue;
            }
        }

        if ([] !== $operations) {
            if ($dryRun) {
                $io->writeln($this->formatOperations($operations));
            } else {
                $this->applyOperations($operations);

                $this->config->installedVersion = self::TO;
                $this->config->flushEznvJson();
            }
        }

        return Command::SUCCESS;
    }

    private function applyOperations(array $operations)
    {
        $filesystem = new Filesystem;

        foreach ($operations as $operation) {
            match ($operation['op']) {
                'delete' => $filesystem->remove($operation['path']),
                'update' => Support::writeJsonToFile($operation['contents'], $operation['path']),
                default => 'placeholder',
            };
        }
    }

    private function createProjectArrayFromDirectory(string $directory): array
    {
        // We have previously verified that is_dir($directory) && file_exists("{$directory}/composer.json").
        $composerJson = Support::readJsonFile("{$directory}/composer.json");

        // We have previously verified that array_key_exists('name', $composerJson) && is_string($name) && '' !== $name.
        // I don't love duplicating the hash and id from project constructor, but I don't want to rely on the project
        // class as in the future it may be one of the breaking changes that we are running this updater to account for.
        $hash = hash('sha256', $directory);

        return [
            'path' => $directory,
            'name' => $composerJson['name'],
            'type' => $composerJson['type'] ?? 'library',
            'hash' => $hash,
            'id' => substr($hash, 0, 12)
        ];
    }

    private function deleteOperation(string $path, string $reason): array
    {
        return [
            'op' => 'delete',
            'path' => $path,
            'reason' => $reason,
        ];
    }

    private function environments(): Generator
    {
        $environments = scandir($this->config->baseDirectory);

        if (false === $environments) {
            throw new RuntimeException();
        }

        foreach ($environments as $directory) {
            if ('.' === $directory || '..' === $directory) {
                continue;
            }

            if (! is_dir($this->config->path($directory))) {
                continue;
            }

            yield $directory;
        }
    }

    private function formatOperations(array $operations): Generator
    {
        foreach ($operations as $operation) {
            if ('delete' === $operation['op']) {
                yield "<info>DELETING:</info> {$operation['path']}";
                yield "<info>REASON:</info> {$operation['reason']}";
            } elseif ('null' === $operation['op']) {
                yield "<info>UNMODIFIED:</info> {$operation['path']}";
            } elseif ('update' === $operation['op']) {
                yield "<info>UPDATING:</info> {$operation['path']}";
                yield "<info>MODIFYING KEY:</info> {$operation['key']}";
                yield '<info>PREVIOUS VALUE:</info> ' . json_encode($operation['before'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                yield '<info>NEW VALUE:</info> ' . json_encode($operation['after'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                throw new LogicException('Should never hit this.');
            }

            yield '';
        }
    }

    private function isValidProjectArray(array $project): bool
    {
        // Not exhaustive, but should be good enough.
        return array_key_exists('hash', $project)
            && array_key_exists('id', $project)
            && array_key_exists('name', $project)
            && '' !== $project['name']
            && array_key_exists('path', $project)
            && $this->isValidProjectDirectory($project['path'])
            && array_key_exists('type', $project);
    }

    private function isValidProjectDirectory(string $directory): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        if (! file_exists("{$directory}/composer.json")) {
            return false;
        }

        $name = Support::readJsonFile("{$directory}/composer.json")['name'] ?? null;

        if (! is_string($name) || '' === $name) {
            return false;
        }

        return true;
    }

    private function nullOperation(string $path): array
    {
        return [
            'op' => 'null',
            'path' => $path,
        ];
    }

    private function updateJsonFileOperation(string $path, array $contents, string $key, $before, $after): array
    {
        return [
            'op' => 'update',
            'path' => $path,
            'contents' => $contents,
            'key' => $key,
            'before' => $before,
            'after' => $after,
        ];
    }
}