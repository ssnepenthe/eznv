<?php

namespace Eznv\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wp', description: "Proxy 'wp' command calls from the current directory to it's connected environment")]
final class WpCommand extends ProxyCommand
{
    protected function getProxyCommand(): string
    {
        return 'wp';
    }
}