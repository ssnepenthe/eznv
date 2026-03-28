<?php

namespace Eznv\Command;

use Closure;
use Exception;
use Eznv\Environment;
use Eznv\Filesystem;
use Eznv\ProcessFactory;
use Eznv\Project;
use InvalidArgumentException;
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

    public function __construct(private Filesystem $fs)
    {}

    public function __invoke(SymfonyStyle $io): int
    {
        $this->io = $io;

        try {
            $project = Project::fromCwd();
            $environment = Environment::fromProject($project);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        // @todo Prompt user to overwrite existing environment.
        if (file_exists($environment->path)) {
            $io->error("A file already exists at environment path {$environment->path}");

            return Command::FAILURE;
        }

        $this->step(
            label: 'Creating environment directory',
            step: function () use ($environment) {
                $this->fs->ensureDirectoryExists($environment->path);
            },
        );

        // @todo error handling after running processes?
        $processFactory = new ProcessFactory($environment->path);

        // @todo Remove stability, repository if/when wp-sqlite-starter is published to packagist
        $this->step(
            label: 'Initializing project',
            process: $processFactory->create(
                'composer',
                'create-project',
                'ssnepenthe/wp-sqlite-starter',
                $environment->path,
                '--stability=dev',
                '--prefer-dist',
                '--repository={"type": "vcs", "url": "https://github.com/ssnepenthe/wp-sqlite-starter.git"}',
                '--no-scripts',
                '--no-progress',
                '--remove-vcs',
                '--no-install',
                '--no-interaction'
            ),
        );

        // @todo would it be better to handle all of this by running actual composer commands? If nothing else, probably slower...
        $this->step(
            label: 'Updating composer.json',
            step: function () use ($environment) {
                $composerJson = $this->fs->readJsonFile($environment->getComposerJsonPath());

                $composerJson['name'] = "eznv/{$environment->project->id}";
                $composerJson['require'][$environment->project->name] = '*';

                if ('wordpress-theme' === $environment->project->type) {
                    unset($composerJson['require']['wp-theme/twentytwentyfive']);
                }

                $composerJson['repositories'][] = [
                    'name' => $environment->project->name,
                    'type' => 'path',
                    'url' => $environment->project->path,
                ];
                $composerJson['extra']['eznv'] = [
                    'project' => $environment->project->toArray(),
                ];

                $this->fs->writeJsonToFile($composerJson, $environment->getComposerJsonPath());
            },
            // @todo gross? maybe just compare mtime before and after step?
            isSuccess: fn () => $this->fs->readJsonFile($environment->getComposerJsonPath())['name'] === "eznv/{$environment->project->id}",
        );

        $this->step(
            label: 'Installing environment dependencies',
            process: $processFactory->create('composer', 'install', '--no-interaction', '--no-progress'),
        );

        $this->step(
            label: 'Generating salts',
            process: $processFactory->create('wp', 'config', 'shuffle-salts'),
        );

        $this->step(
            label: 'Enabling WP_DEBUG',
            process: $processFactory->create('wp', 'config', 'set', 'WP_DEBUG', 'true', '--raw'),
        );

        // @todo allow user to override all options?
        // @todo we also need better host and port handling - what if a user wants to run multiple environments at once?
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
