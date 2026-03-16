<?php

namespace Eznv\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wp', description: "Proxy 'wp' command calls from the current directory to it's connected environment")]
final class WpCommand extends ProxyCommand
{
    protected function getProcessTimeout(): int
    {
        // Might come to regret this down the road but it prevents timeouts if I spend too much time on a help screen.
        return 60 * 10;
    }

    protected function getProxyCommand(): string
    {
        return 'wp';
    }
}