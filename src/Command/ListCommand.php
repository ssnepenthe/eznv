<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'list', description: 'List all eznv environments')]
final class ListCommand
{
    public function __invoke(SymfonyStyle $io, #[Option] bool $orphaned = false): int
    {
        $finder = new EnvironmentFinder;

        $io->table(
            ['ID', 'Name', 'Path'],
            array_map(
                fn (Environment $environment) => [$environment->project->id, $environment->project->name, $environment->project->path],
                $orphaned ? $finder->findOrphaned() : $finder->findAll()
            )
        );

        return Command::SUCCESS;
    }
}