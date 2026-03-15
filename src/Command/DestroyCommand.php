<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

// @todo default command timeout @ 10 minutes or so?
#[AsCommand(name: 'destroy', description: 'Destroy the environment for the current directory')]
final class DestroyCommand
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

        // @todo prompt user to delete anyway.
        if (! $environment->isInitialized()) {
            $io->error("Environment {$identifier} has not been initialized");

            return Command::FAILURE;
        }

        $relativeEnvironmentPath = Support::makePathRelativeToHome($environment->path);

        $io->caution("Environment directory at {$relativeEnvironmentPath} and all of it's contents will be deleted");

        $answer = $io->confirm('Do you wish to continue?', false);

        if (! $answer) {
            $io->warning('Operation cancelled');

            return Command::SUCCESS;
        }

        (new Filesystem)->remove($environment->path);

        return Command::SUCCESS;
    }
}