<?php

namespace Eznv\Command;

use Exception;
use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\ProcessFactory;
use Eznv\Project;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'adopt', description: 'Adopt an orphaned environment')]
final class AdoptCommand
{
    public function __invoke(SymfonyStyle $io, #[Argument] string $identifier): int
    {
        try {
            $originalEnvironment = (new EnvironmentFinder)->find($identifier);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (! $originalEnvironment->isOrphaned()) {
            $io->error("Cannot adopt environment from {$originalEnvironment->project->name} - it is not orphaned");

            return Command::FAILURE;
        }

        try {
            $newProject = Project::fromCwd();
            $newEnvironment = Environment::fromProject($newProject);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (file_exists($newEnvironment->path)) {
            $io->error('Cannot adopt a new environment - this project already has an attached environment');

            return Command::FAILURE;
        }

        $continue = $io->confirm("Attempting to adopt orphaned environment from {$originalEnvironment->project->name}. Continue?");

        if (! $continue) {
            $io->info('Aborted by user');

            return Command::SUCCESS;
        }

        $composerJson = Support::readJsonFile($originalEnvironment->getComposerJsonPath());

        $composerJson['name'] = "eznv/{$newEnvironment->project->id}";

        unset($composerJson['require'][$originalEnvironment->project->name]);

        $composerJson['repositories'] = array_values(array_filter(
            $composerJson['repositories'],
            fn (array $repository) => ($repository['name'] ?? 'unknown') !== $originalEnvironment->project->name
        ));

        $composerJson['repositories'][] = [
            'name' => $newEnvironment->project->name,
            'type' => 'path',
            'url' => $newEnvironment->project->path,
        ];

        $composerJson['require'][$newEnvironment->project->name] = '*';

        if ('wordpress-theme' === $newEnvironment->project->type) {
            unset($composerJson['require']['wp-theme/twentytwentyfive']);
        } else {
            $composerJson['require']['wp-theme/twentytwentyfive'] = '*';
        }

        $composerJson['extra']['eznv']['project'] = $newEnvironment->project->toArray();

        Support::writeJsonToFile($composerJson, $originalEnvironment->getComposerJsonPath());

        // @todo do we need to delete all locations where composer packages are installed? not sure if necessary, have to test
        // can use composer show --path --format=json to get list of all packages with installed paths.

        $fs = new Filesystem;
        $fs->rename($originalEnvironment->path, $newEnvironment->path);

        (new ProcessFactory($newEnvironment->path))
            ->create('composer', 'install', '--no-interaction', '--no-progress')
            ->run();

        return Command::SUCCESS;
    }
}