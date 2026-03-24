<?php

namespace Eznv\Command;

use Eznv\EnvironmentFinder;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'prune', description: 'Clean up orphaned environments')]
final class PruneCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $orphaned = (new EnvironmentFinder)->findOrphaned();

        if ([] === $orphaned) {
            $io->writeln('No orphaned environments found');

            return Command::SUCCESS;
        }

        foreach ($orphaned as $environment) {
            $relativeEnvironmentPath = Support::makePathRelativeToHome($environment->path);

            $io->caution("Environment directory at {$relativeEnvironmentPath} and all of it's contents will be deleted");

            $answer = $io->confirm('Do you wish to continue?', false);

            if (! $answer) {
                $io->warning('Operation cancelled');

                return Command::SUCCESS;
            }

            (new Filesystem)->remove($environment->path);
        }

        return Command::SUCCESS;
    }
}