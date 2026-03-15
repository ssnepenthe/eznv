<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\Eznv;
use Eznv\ProcessFactory;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'info', description: 'Display information about the current WordPress environment')]
final class InfoCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $identifier = getcwd();

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

        $dataDirectory = Eznv::instance()->baseDirectory;

        $processFactory = new ProcessFactory($environment->path);
        $wpVersionProcess = $processFactory->create('wp', 'core', 'version')->mustRun();
        $sqliteVersionProcess = $processFactory->create('wp', 'plugin', 'get', 'sqlite-database-integration', '--field=version')->mustRun();

        $io->definitionList(
            'EZNV',
            ['Data Directory' => Support::makePathRelativeToHome($dataDirectory)],
            new TableSeparator(),
            'Project',
            ['Name' => $environment->project->name],
            ['Path' => Support::makePathRelativeToHome($environment->project->path)],
            ['Type' => $environment->project->type],
            ['ID' => $environment->project->id],
            new TableSeparator(),
            'Environment (paths relative to eznv data directory)',
            ['Base Path' => Path::makeRelative($environment->path, $dataDirectory)],
            ['WordPress Path' => Path::makeRelative($environment->getWordPressPath(), $dataDirectory)],
            ['WP Config Path' => Path::makeRelative($environment->getWpConfigPath(), $dataDirectory)],
            ['DB Dropin Path' => Path::makeRelative($environment->getDatabaseDropinPath(), $dataDirectory)],
            ['Database Path' => Path::makeRelative($environment->getDatabasePath(), $dataDirectory)],
            ['WP Version' => trim($wpVersionProcess->getOutput())],
            ['SQLite Integration Version' => trim($sqliteVersionProcess->getOutput())],
        );

        return Command::SUCCESS;
    }
}