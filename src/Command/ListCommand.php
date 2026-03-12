<?php

namespace Eznv\Command;

use Eznv\EnvironmentFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

// @todo "list" name conflicts with the built-in symfony/console list command which lists all registered commands
// but i dont really like env-list
#[AsCommand(name: 'env-list', description: 'List all eznv environments')]
final class ListCommand
{
    public function __invoke(): int
    {
        $environments = (new EnvironmentFinder)->findAll();

        // @todo Not sure how I want to display this and what info to include yet.
        dump($environments);

        return Command::SUCCESS;
    }
}