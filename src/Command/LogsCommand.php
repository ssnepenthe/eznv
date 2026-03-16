<?php

namespace Eznv\Command;

use Eznv\Environment;
use Eznv\EnvironmentFinder;
use Eznv\ProcessFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'logs', description: 'Tail the debug.log for the current environment')]
final class LogsCommand
{
    public function __invoke(OutputInterface $output, SymfonyStyle $io): int
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

        if (! $environment->isInitialized()) {
            $io->error("Environment {$identifier} has not been initialized");

            return Command::FAILURE;
        }

        $processFactory = new ProcessFactory($environment->path);

        // @todo Maybe we should just run wp eval instead to handle this in a single operation?
        $process = $processFactory->create('wp', 'config', 'has', 'WP_DEBUG_LOG');
        $process->run();

        if (0 !== $process->getExitCode()) {
            $io->error('The WP_DEBUG_LOG constant is not defined - run "wp config set WP_DEBUG_LOG true --raw" and try again');

            return Command::FAILURE;
        }

        $debugLogEnabled = trim($processFactory->create('wp', 'config', 'get', 'WP_DEBUG_LOG', '--format=json')->mustRun()->getOutput());

        if ('true' !== $debugLogEnabled) {
            $io->error('The WP_DEBUG_LOG constant must be set to true - run "wp config set WP_DEBUG_LOG true --raw" and try again');

            return Command::FAILURE;
        }

        $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;

        $processFactory
            ->create('tail', '-F', $environment->getDebugLogPath())
            ->setTimeout(null)
            ->run(function ($type, $buffer) use ($errorOutput, $output): void {
                if (Process::ERR === $type) {
                    $errorOutput->write($buffer);
                } else {
                    $output->write($buffer);
                }
            });

        return Command::SUCCESS;
    }
}