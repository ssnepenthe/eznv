<?php

namespace Eznv\Command;

use Closure;
use Eznv\Environment;
use Eznv\Process;
use Eznv\Project;
use Eznv\Template;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process as SymfonyProcess;

#[AsCommand(name: 'init', description: 'Initialize a WordPress environment for the current directory')]
final class InitCommand
{
    private ?SymfonyStyle $io = null;
    private ?HelperSet $helperSet = null;

    public function __invoke(SymfonyStyle $io, #[Argument] string $directory = ''): int
    {
        $this->io = $io;

        // @todo strip /run/host to save us from ourselves when using distrobox?
        // @todo getcwd() can return false - update all commands.
        if ('' === $directory) {
            $directory = getcwd();
        }

        $project = new Project($directory);
        $environment = $this->step(
            label: 'Preparing environment directories',
            step: fn () => $project->environment(),
            isSuccess: fn ($environment) => is_dir(Environment::getBaseDirectory()) && is_dir($environment->path)
        );

        // @todo Prompt user to overwrite existing environment.
        if ($environment->isInitialized()) {
            $io->error('An environment has already been initialized for this directory.');

            return Command::FAILURE;
        }

        // @todo server config (preferred port, etc) in extra
        $this->step(
            label: 'Writing composer.json file',
            step: fn () => $environment->writeComposerJson(),
            isSuccess: fn () => file_exists("{$environment->path}/composer.json"),
        );
        $this->step(
            label: 'Writing wp-cli.yml file',
            step: fn () => $environment->writeWpCliYml(),
            isSuccess: fn () => file_exists("{$environment->path}/wp-cli.yml"),
        );

        // @todo error handling after running processes?
        $factory = new Process($environment->path);

        $this->step(
            label: 'Installing environment dependencies',
            process: $factory->create('composer', 'install', '--no-interaction', '--no-progress'),
        );

        $this->step(
            label: 'Creating wp-config.php',
            step: function () use ($environment) {
                // @todo we can pretty safely assume this file doesnt exist already, but even if it did we probably want to overwrite it.
                if (! file_exists("{$environment->path}/wordpress/wp-config.php")) {
                    Template::write(__DIR__ . '/../../stubs/wp-config.stub', "{$environment->path}/wordpress/wp-config.php");
                }
            },
            isSuccess: fn () => file_exists("{$environment->path}/wordpress/wp-config.php"),
        );

        $this->step(
            label: 'Generating salts',
            process: $factory->create('wp', 'config', 'shuffle-salts'),
        );

        $this->step(
            label: 'Setting up db.php dropin',
            step: function () use ($environment) {
                $dropinPath = "{$environment->path}/wordpress/wp-content/db.php";

                // @todo we can pretty much guarantee this file doesnt already exist but even if it did we probably want to overwrite it
                // @todo move this to composer post-install/post-update script?
                if (! file_exists($dropinPath)) {
                    Template::write(
                        "{$environment->path}/wordpress/wp-content/plugins/sqlite-database-integration/db.copy",
                        $dropinPath,
                        [
                            'SQLITE_IMPLEMENTATION_FOLDER_PATH' => "{$environment->path}/wordpress/wp-content/plugins/sqlite-database-integration",
                            'SQLITE_PLUGIN' => 'sqlite-database-integration/load.php',
                        ]
                    );
                }
            },
            isSuccess: fn () => file_exists("{$environment->path}/wordpress/wp-content/db.php"),
        );

        // @todo allow user to override all options?
        // @todo we also need better port handling - what if a user wants to run multiple environments at once? looks like --url will map to "siteurl" and "home" options
        $this->step(
            label: 'Running WordPress installer',
            process: $factory->create(
                'wp',
                'core',
                'install',
                '--url=localhost:8080',
                '--title=test',
                '--admin_user=admin',
                '--admin_password=password',
                '--admin_email=admin@example.com',
                '--skip-email'
            ),
        );

        $this->step(
            label: 'Setting permalink structure',
            process: $factory->create('wp', 'rewrite', 'structure', '/%postname%/'),
        );

        // @todo notify user of success and next steps (serve command, etc).

        return Command::SUCCESS;
    }

    private function step(string $label, ?Closure $step = null, ?SymfonyProcess $process = null, ?Closure $isSuccess = null)
    {
        if ((null === $step && null === $process) || (null !== $step && null !== $process)) {
            throw new InvalidArgumentException('Must call "step" with only one of "step" or "process" args');
        }

        // @todo Would itmake more sense to just print the command we are running and then dump the raw output of that command?
        if ($process instanceof SymfonyProcess) {
            $step = fn () => $this->getProcessHelper()->run($this->io, $process);
            $isSuccess ??= fn (SymfonyProcess $process) => $process->isSuccessful();
        }

        $isSuccess ??= fn () => true;

        $this->io->write("{$label}...");

        $result = $step();

        if ($isSuccess($result)) {
            $this->io->write(' <info>SUCCESS!</info>', true);
        } else {
            $this->io->write(' <error>FAILED!</error>', true);
        }

        return $result;
    }

    private function getProcessHelper(): ProcessHelper
    {
        if (! $this->helperSet instanceof HelperSet) {
            $this->helperSet = new HelperSet([
                new ProcessHelper(),
                new DebugFormatterHelper(),
            ]);
        }

        return $this->helperSet->get('process');
    }
}
