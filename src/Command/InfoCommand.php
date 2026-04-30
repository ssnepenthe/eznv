<?php

namespace Eznv\Command;

use Exception;
use Eznv\EnvironmentFinder;
use Eznv\Eznv;
use Eznv\ProcessFactory;
use Eznv\Support;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'info', description: 'Display information about the current WordPress environment')]
final class InfoCommand
{
    public function __construct(private Eznv $config, private EnvironmentFinder $finder)
    {}

    public function __invoke(SymfonyStyle $io): int
    {
        try {
            $environment = $this->finder->findByProjectDirectory(Support::getCwd());
        } catch (Exception $e) { // @todo more specific exception type
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $dataDirectory = $this->config->baseDirectory;

        $processFactory = new ProcessFactory($environment->path);

        [$dbPath, $wpVersion, $sqliteIntegrationVersion] = $this->run([
            $processFactory->create('wp', 'eval', 'echo defined("FQDB") ? FQDB : "(UNKNOWN)";', '--skip-plugins', '--skip-themes', '--skip-packages'),
            $processFactory->create('wp', 'core', 'version', '--skip-plugins', '--skip-themes', '--skip-packages'),
            $processFactory->create('wp', 'eval', 'echo defined("SQLITE_DRIVER_VERSION") ? SQLITE_DRIVER_VERSION : "(UNKNOWN)";', '--skip-plugins', '--skip-themes', '--skip-packages'),
        ]);

        $io->definitionList(
            'EZNV',
            ['Data Directory' => Support::makePathRelativeToHome($dataDirectory)],
            new TableSeparator(),
            'Project',
            ['Name' => $environment->project->name],
            ['Path' => Support::makePathRelativeToHome($environment->project->path)],
            ['Type' => $environment->project->type],
            ['ID' => $environment->project->id],
            new TableSeparator(),
            'Environment (paths relative to eznv data directory)',
            ['Base Path' => Path::makeRelative($environment->path, $dataDirectory)],
            ['WordPress Path' => Path::makeRelative($environment->getWordPressPath(), $dataDirectory)],
            ['WP Config Path' => Path::makeRelative($environment->getWpConfigPath(), $dataDirectory)],
            ['DB Dropin Path' => Path::makeRelative($environment->getDatabaseDropinPath(), $dataDirectory)],
            ['Database Path' => Path::makeRelative($dbPath, $dataDirectory)],
            ['WP Version' => $wpVersion],
            ['SQLite Integration Version' => $sqliteIntegrationVersion],
        );

        return Command::SUCCESS;
    }

    /**
     * @param Process[] $processes
     * @return string[]
     */
    private function run(array $processes): array
    {
        $results = [];
        array_walk($processes, fn (Process $process) => $process->start());

        while ([] !== $processes) {
            foreach ($processes as $key => $process) {
                if ($process->isRunning()) {
                    try{
                        $process->checkTimeout();
                    } catch (ProcessTimedOutException) {
                        $results[$key] = 'UNKNOWN: Process timed out';
                        unset($processes[$key]);
                    }
                } else {
                    $results[$key] = trim(0 === $process->getExitCode() ? $process->getOutput() : 'UNKNOWN: Process exited unsuccessfully');
                    unset($processes[$key]);
                }
            }

            usleep(50000);
        }

        return $results;
    }
}