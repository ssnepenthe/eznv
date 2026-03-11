<?php

namespace Eznv\Command;

use Eznv\Process;
use Eznv\Project;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process as SymfonyProcess;

abstract class ProxyCommand extends Command
{
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ('' === $this->getProxyCommand()) {
            throw new LogicException('getProxyCommand() method must return a non-empty string');
        }
    }

    protected function configure(): void
    {
        $this->ignoreValidationErrors();

        $this->addArgument('proxy-command', InputArgument::IS_ARRAY, 'The proxy command you want to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // @todo Realistically we could do like the composer global command - stringify input and re-parse.
        if (! $input instanceof ArgvInput) {
            $io->error('@todo This command only works with ArgvInput at the moment');

            return Command::FAILURE;
        }

        $directory = getcwd();

        $project = new Project($directory);
        $environment = $project->environment();

        $process = (new Process($environment->path))
            ->create($this->getProxyCommand(), ...$input->getRawTokens(true))
            // @todo notify user if not supported they can't use interactive commands?
            ->setTty(SymfonyProcess::isTtySupported());

        $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;

        $process->run(function ($type, $buffer) use ($errorOutput, $output): void {
            if (SymfonyProcess::ERR === $type) {
                $errorOutput->write($buffer);
            } else {
                $output->write($buffer);
            }
        });

        return $process->getExitCode() ?? Command::SUCCESS;
    }

    abstract protected function getProxyCommand(): string;
}