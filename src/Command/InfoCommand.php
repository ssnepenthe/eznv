<?php

namespace Eznv\Command;

use Eznv\EnvironmentManager;
use Eznv\ProjectManager;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'info', description: 'Display information about the current WordPress environment')]
final class InfoCommand
{
    public function __invoke(SymfonyStyle $io, #[Argument] string $directory = ''): int
    {
        $project = (new ProjectManager)->createForDirectory($directory);
        $environment = (new EnvironmentManager)->createForProject($project);

        if (! $environment->isInitialized()) {
            $io->error('Enviroment has not been initialized for this directory');

            return Command::FAILURE;
        }

        // @todo option to show full path/short path?
        $io->definitionList(
            ['Path' => Support::makePathRelativeToHome($project->path)],
            ['ID' => $project->id],
            new TableSeparator(),
            ['Environment' => Support::makePathRelativeToHome($environment->path)],
            ['WordPress' => Support::makePathRelativeToHome($environment->getWordPressPath())],
            ['Database' => Support::makePathRelativeToHome($environment->getDatabasePath())],
            // @todo Should we get via WP-CLI instead?
            ['WP Version' => $environment->getInstalledPackageVersion('roots/wordpress') ?: 'Not installed'],
        );

        return Command::SUCCESS;
    }
}