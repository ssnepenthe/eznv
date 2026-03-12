<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\Process;
use Eznv\Support;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process as SymfonyProcess;

// @todo is this redundant given our wp proxy command? I feel like the wp proxy command is just a nice-to-have whereas
// the serve command is basically half the point of this eznv package...
#[AsCommand(name: 'serve', description: 'Start a WordPress server for the current directory.')]
final class ServeCommand
{
    public function __invoke(
        OutputInterface $output,
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

        $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;

        // @todo configurable port at minimum.
        // @todo what if we sent to background and redirected all output to log file?
        // @todo set PHP_CLI_SERVER_WORKERS env var by default? Or at least recommend user to set it.
        (new Process($environment->path))
            ->create('wp', 'server')
            ->setTty(SymfonyProcess::isTtySupported()) // @todo Don't remember why we set TTY mode, but don't think we need it.
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