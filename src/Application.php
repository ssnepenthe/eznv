<?php

namespace Eznv;

use Eznv\Command\CommandListCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;

final class Application extends ConsoleApplication
{
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->setDefaultCommand('cmd-list');
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[]
     */
    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new CommandListCommand(), new CompleteCommand(), new DumpCompletionCommand()];
    }
}