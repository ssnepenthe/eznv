<?php

namespace Eznv\Command;

use Eznv\Project;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'info', description: 'Display information about the current WordPress environment')]
final class InfoCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument]
        string $directory = ''
    ): int {
        if ('' === $directory) {
            $directory = getcwd();
        }

        $project = new Project($directory);
        $environment = $project->environment();

        if (! $environment->isInitialized()) {
            $io->error('No environment exists for this directory.');

            return Command::FAILURE;
        }

        // @todo option to show full path/short path?
        $io->definitionList(
            ['Path' => Support::makePathRelativeToHome($project->path)],
            ['ID' => $project->hash],
            new TableSeparator(),
            ['Environment' => Support::makePathRelativeToHome($environment->path)],
            // @todo getWordPressPath method?
            ['WordPress' => Support::makePathRelativeToHome("{$environment->path}/wordpress")],
            ['Database' => Support::makePathRelativeToHome($environment->getDatabasePath())],
            // @todo Should we get via WP-CLI instead?
            ['WP Version' => $environment->getInstalledVersion('roots/wordpress') ?: 'Not installed'],
        );

        return Command::SUCCESS;
    }
}