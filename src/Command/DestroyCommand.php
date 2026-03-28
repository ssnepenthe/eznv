<?php

namespace Eznv\Command;

use Exception;
use Eznv\EnvironmentFinder;
use Eznv\Filesystem;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'destroy', description: 'Destroy the environment for the current directory')]
final class DestroyCommand
{
    public function __construct(private EnvironmentFinder $finder, private Filesystem $fs)
    {}

    public function __invoke(SymfonyStyle $io): int
    {
        try {
            $environment = $this->finder->findByProjectDirectory(Support::getCwd());
        } catch (Exception $e) { // @todo more specific exception type
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $relativeEnvironmentPath = Support::makePathRelativeToHome($environment->path);

        $io->caution("Environment directory at {$relativeEnvironmentPath} and all of it's contents will be deleted");

        $answer = $io->confirm('Do you wish to continue?', false);

        if (! $answer) {
            $io->warning('Operation cancelled');

            return Command::SUCCESS;
        }

        $this->fs->removeDirectory($environment->path);

        return Command::SUCCESS;
    }
}