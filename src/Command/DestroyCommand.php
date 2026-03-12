<?php

namespace Eznv\Command;

use Eznv\EnvironmentManager;
use Eznv\ProjectManager;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'destroy', description: 'Destroy the environment for the current directory')]
final class DestroyCommand
{
    public function __invoke(SymfonyStyle $io, #[Argument] string $directory = ''): int
    {
        $project = (new ProjectManager)->createForDirectory($directory);
        $environment = (new EnvironmentManager)->createForProject($project);

        $relativeEnvironmentPath = Support::makePathRelativeToHome($environment->path);

        // @todo prompt user to delete anyway.
        // @todo We are always creating environment directory in constructor so if directory didn't exist, it will have been created by this point and we will always error out here.
        if (! $environment->isInitialized()) {
            $io->error("Environment directory does not exist or has not been fully initialized at path {$relativeEnvironmentPath}");

            return Command::FAILURE;
        }

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