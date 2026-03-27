<?php

namespace Eznv;

use Eznv\Command;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class Container
{
    private array $creators;
    private array $resolved = [];

    private static ?self $instance = null;

    private function __construct()
    {
        $this->creators = [
            EnvironmentFinder::class => fn (Container $c) => new EnvironmentFinder($c->get(Eznv::class)),
            Eznv::class => fn () => new Eznv(),
            Filesystem::class => fn () => new Filesystem(),

            'commands' => function (Container $c) {
                $environmentFinder = $c->get(EnvironmentFinder::class);
                $eznv = $c->get(Eznv::class);

                return [
                    new Command\AdoptCommand($environmentFinder),
                    new Command\ComposerCommand($environmentFinder),
                    new Command\DestroyCommand($environmentFinder),
                    new Command\ForCommand($environmentFinder),
                    new Command\InfoCommand($eznv, $environmentFinder),
                    new Command\InitCommand(),
                    new Command\ListCommand($environmentFinder),
                    new Command\LogsCommand($environmentFinder),
                    new Command\PostUpdateCommand($eznv),
                    new Command\PruneCommand($environmentFinder),
                    new Command\ServeCommand($environmentFinder),
                    new Command\ShellCommand($environmentFinder),
                    new Command\WpCommand($environmentFinder),
                ];
            },
        ];
    }

    public static function instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get(string $key)
    {
        if (! array_key_exists($key, $this->resolved)) {
            if (! array_key_exists($key, $this->creators)) {
                throw new RuntimeException();
            }

            $this->resolved[$key] = ($this->creators[$key])($this);
        }

        return $this->resolved[$key];
    }
}