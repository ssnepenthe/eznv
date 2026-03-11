<?php

namespace Eznv\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'composer', description: "Proxy 'composer' command calls from the current directory to it's connected environment")]
final class ComposerCommand extends ProxyCommand
{
    protected function getProxyCommand(): string
    {
        return 'composer';
    }
}