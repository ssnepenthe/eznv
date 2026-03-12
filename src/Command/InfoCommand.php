<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
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

        // @todo option to show full path/short path?
        $io->definitionList(
            ['Path' => Support::makePathRelativeToHome($environment->project->path)],
            ['ID' => $environment->project->id],
            new TableSeparator(),
            ['Environment' => Support::makePathRelativeToHome($environment->path)],
            ['WordPress' => Support::makePathRelativeToHome($environment->getWordPressPath())],
            ['Database' => Support::makePathRelativeToHome($environment->getDatabasePath())],
            // @todo Should we get via WP-CLI instead? This could fall out of sync if updates applied via wp-admin or wp-cli.
            ['WP Version' => $environment->getInstalledPackageVersion('roots/wordpress') ?: 'Not installed'],
        );

        return Command::SUCCESS;
    }
}