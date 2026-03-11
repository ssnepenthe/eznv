<?php

namespace Eznv\Command;

use Eznv\Process;
use Eznv\Project;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

// @todo is this redundant given our wp proxy command? I feel like the wp proxy command is just a nice-to-have whereas
// the serve command is basically half the point of this eznv package...
#[AsCommand(name: 'serve', description: 'Start a WordPress server for the current directory.')]
final class ServeCommand
{
    public function __invoke(OutputInterface $output, #[Argument] string $directory = ''): int {
        if ('' === $directory) {
            $directory = getcwd();
        }

        $project = new Project($directory);
        $environment = $project->environment();

        $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;

        // @todo configurable port at minimum.
        // @todo what if we sent to background and redirected all output to log file?
        (new Process($environment->path))
            ->create('wp', 'server')
            ->setTty(SymfonyProcess::isTtySupported())
            ->setTimeout(null)
            ->run(function ($type, $buffer) use ($errorOutput, $output): void {
                if (SymfonyProcess::ERR === $type) {
                    $errorOutput->write($buffer);
                } else {
                    $output->write($buffer);
                }
            });

        return Command::SUCCESS;
    }
}