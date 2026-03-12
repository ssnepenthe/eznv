<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\Process;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'info', description: 'Display information about the current WordPress environment')]
final class InfoCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(suggestedValues: [Support::class, 'suggestEnvironmentIdentifiers'])] string $identifier = ''
    ): int {
        if ('' === $identifier) {
            $identifier = getcwd();
        }

        if (false === $identifier) {
            $io->error('Unable to determine project directory');

            return Command::FAILURE;
        }

        $environment = (new EnvironmentFinder)->find($identifier);

        if (! $environment instanceof Environment) {
            $io->error("Unable to find environment {$identifier}");

            return Command::FAILURE;
        }

        if (! $environment->isInitialized()) {
            $io->error("Environment {$identifier} has not been initialized");

            return Command::FAILURE;
        }

        $processFactory = new Process($environment->path);
        $wpVersionProcess = $processFactory->create('wp', 'core', 'version')->mustRun();
        $sqliteVersionProcess = $processFactory->create('wp', 'plugin', 'get', 'sqlite-database-integration', '--field=version')->mustRun();

        $io->definitionList(
            'Project',
            ['Name' => $environment->project->name],
            ['Path' => Support::makePathRelativeToHome($environment->project->path)],
            ['Type' => $environment->project->type],
            ['ID' => $environment->project->id],
            new TableSeparator(),
            'Environment',
            ['Path' => Support::makePathRelativeToHome($environment->path)],
            ['WordPress Path' => Path::makeRelative($environment->getWordPressPath(), $environment->path)],
            ['WP Config Path' => Path::makeRelative($environment->getWpConfigPath(), $environment->path)],
            ['DB Dropin Path' => Path::makeRelative($environment->getDatabaseDropinPath(), $environment->path)],
            ['Database Path' => Path::makeRelative($environment->getDatabasePath(), $environment->path)],
            ['WP Version' => trim($wpVersionProcess->getOutput())],
            ['SQLite Integration Version' => trim($sqliteVersionProcess->getOutput())],
        );

        return Command::SUCCESS;
    }
}