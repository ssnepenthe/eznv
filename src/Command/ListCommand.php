<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

// @todo "list" name conflicts with the built-in symfony/console list command which lists all registered commands
// but i dont really like env-list
#[AsCommand(name: 'env-list', description: 'List all eznv environments')]
final class ListCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $io->table(
            ['ID', 'Name', 'Path'],
            array_map(
                fn (Environment $environment) => [$environment->project->id, $environment->project->name, $environment->project->path],
                (new EnvironmentFinder)->findAll()
            )
        );

        return Command::SUCCESS;
    }
}