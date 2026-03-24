<?php

namespace Eznv\Command;

use Symfony\Component\Console\Command\ListCommand;

final class CommandListCommand extends ListCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('cmd-list');
    }
}