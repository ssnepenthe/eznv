<?php

namespace Eznv\Command;

use Closure;
use Eznv\EnvironmentManager;
use Eznv\ProcessFactory;
use Eznv\Support;
use Eznv\Template;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'init', description: 'Initialize a WordPress environment for the current directory')]
final class InitCommand
{
    private ?SymfonyStyle $io = null;
    private ?HelperSet $helperSet = null;

    public function __invoke(SymfonyStyle $io, #[Argument] string $directory = ''): int
    {
        $this->io = $io;

        if ('' === $directory) {
            $directory = getcwd();
        }

        if (false === $directory) {
            $io->error('Unable to determine project directory');

            return Command::FAILURE;
        }

        $manager = new EnvironmentManager;

        $project = $manager->createProject($directory);
        $environment = $manager->createForProject($project);

        $this->step(
            label: 'Preparing environment directories',
            step: fn () => Support::ensureDirectoryExists($environment->path),
        );

        // @todo Prompt user to overwrite existing environment.
        if ($environment->isInitialized()) {
            $io->error('An environment has already been initialized for this directory.');

            return Command::FAILURE;
        }

        // @todo server config (preferred port, etc) in extra
        $this->step(
            label: 'Writing composer.json file',
            step: fn () => $manager->writeComposerJson($environment, $project),
            isSuccess: fn () => file_exists($environment->getComposerJsonPath()),
        );
        $this->step(
            label: 'Writing wp-cli.yml file',
            step: fn () => $manager->writeWpCliYml($environment),
            isSuccess: fn () => file_exists($environment->getWpCliYmlPath()),
        );

        // @todo error handling after running processes?
        $processFactory = new ProcessFactory($environment->path);

        $this->step(
            label: 'Installing environment dependencies',
            process: $processFactory->create('composer', 'install', '--no-interaction', '--no-progress'),
        );

        $this->step(
            label: 'Creating wp-config.php',
            step: function () use ($environment) {
                // @todo we can pretty safely assume this file doesnt exist already, but even if it did we probably want to overwrite it.
                if (! file_exists($environment->getWpConfigPath())) {
                    Template::write(__DIR__ . '/../../stubs/wp-config.stub', $environment->getWpConfigPath());
                }
            },
            isSuccess: fn () => file_exists($environment->getWpConfigPath()),
        );

        $this->step(
            label: 'Generating salts',
            process: $processFactory->create('wp', 'config', 'shuffle-salts'),
        );

        $this->step(
            label: 'Setting up db.php dropin',
            step: function () use ($environment) {
                // @todo we can pretty much guarantee this file doesnt already exist but even if it did we probably want to overwrite it
                // @todo move this to composer post-install/post-update script?
                if (! file_exists($environment->getDatabaseDropinPath())) {
                    Template::write(
                        "{$environment->path}/wordpress/wp-content/plugins/sqlite-database-integration/db.copy",
                        $environment->getDatabaseDropinPath(),
                        [
                            'SQLITE_IMPLEMENTATION_FOLDER_PATH' => "{$environment->path}/wordpress/wp-content/plugins/sqlite-database-integration",
                            'SQLITE_PLUGIN' => 'sqlite-database-integration/load.php',
                        ]
                    );
                }
            },
            isSuccess: fn () => file_exists($environment->getDatabaseDropinPath()),
        );

        // @todo allow user to override all options?
        // @todo we also need better port handling - what if a user wants to run multiple environments at once? looks like --url will map to "siteurl" and "home" options
        $this->step(
            label: 'Running WordPress installer',
            process: $processFactory->create(
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
            process: $processFactory->create('wp', 'rewrite', 'structure', '/%postname%/'),
        );

        // @todo notify user of success and next steps (serve command, etc).

        return Command::SUCCESS;
    }

    private function step(string $label, ?Closure $step = null, ?Process $process = null, ?Closure $isSuccess = null)
    {
        if ((null === $step && null === $process) || (null !== $step && null !== $process)) {
            throw new InvalidArgumentException('Must call "step" with only one of "step" or "process" args');
        }

        // @todo Would itmake more sense to just print the command we are running and then dump the raw output of that command?
        if ($process instanceof Process) {
            $step = fn () => $this->getProcessHelper()->run($this->io, $process);
            $isSuccess ??= fn (Process $process) => $process->isSuccessful();
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
