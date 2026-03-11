<?php

namespace Eznv\Command;

use Eznv\Process;
use Eznv\Project;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process as SymfonyProcess;

#[AsCommand(name: 'shell')]
final class ShellCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        if (! SymfonyProcess::isTtySupported()) {
            $io->error('TTY support is required');

            return Command::FAILURE;
        }

        $directory = getcwd();

        $project = new Project($directory);
        $environment = $project->environment();

        $shell = basename(Support::getEnv('SHELL') ?: 'bash');
        $relative = Support::makePathRelativeToHome($environment->path);

        $io->writeln('');
        $io->writeln("<info>Launching {$shell} shell</info>");
        $io->writeln("<comment>Directory: {$relative}");
        $io->writeln("<comment>Type 'exit' or press 'ctrl + d' to exit</comment>");
        $io->writeln('');

        // @todo Environment variable to indicate we are in an eznv shell? Can't really think of a need for that at the moment.
        $process = (new Process($environment->path))
            ->create($shell, '-l', '-i') // @todo verify login and interactive flags are the same across shells?
            ->setTty(true)
            ->setEnv($_ENV) // @todo Any reason we SHOULD NOT be inheriting environment?
            ->setTimeout(null);

        $process->run();

        return $process->getExitCode() ?? Command::SUCCESS;
    }
}