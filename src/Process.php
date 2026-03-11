<?php

namespace Eznv;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process as SymfonyProcess;

final class Process
{
    private static array $foundExecutables = [];

    public function __construct(private string $workingDirectory)
    {}

    public function create(string $executable, string ...$command): SymfonyProcess
    {
        $executable = self::find($executable);

        $process = new SymfonyProcess([$executable, ...$command]);
        $process->setWorkingDirectory($this->workingDirectory);

        return $process;
    }

    private static function find(string $executable): string
    {
        if (! array_key_exists($executable, self::$foundExecutables)) {
            $found = (new ExecutableFinder())->find($executable);

            if (! $found) {
                throw new \RuntimeException("The \"{$executable}\" executable could not be found in your PATH.");
            }

            self::$foundExecutables[$executable] = $found;
        }

        return self::$foundExecutables[$executable];
    }
}