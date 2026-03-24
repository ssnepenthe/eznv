<?php

namespace Eznv\Command;

use Exception;
use Eznv\Environment;
use Eznv\EnvironmentFinder;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'for', description: 'Allows running commands in a specific project directory')]
class ForCommand extends Command
{
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('project')) {
            $suggestions->suggestValues($this->suggestProjects($input));

            return;
        }

        if ($input->mustSuggestArgumentValuesFor('command-name')) {
            $suggestions->suggestValues($this->suggestCommandNames($input));

            return;
        }

        $application = $this->getApplication();

        if ($application->has($commandName = $input->getArgument('command-name'))) {
            $newInput = $this->createSubcommandInput($input);
            $command = $application->find($commandName);
            $command->mergeApplicationDefinition();
            $newInput->bind($command->getDefinition());
            $command->complete($newInput, $suggestions);
        }
    }

    protected function configure(): void
    {
        $this->ignoreValidationErrors();

        $this->addArgument('project', InputArgument::REQUIRED, 'An identifier for the project connected to the environment you want to run the command in (can be name, id, or path)')
            ->addArgument('command-name', InputArgument::REQUIRED, 'The eznv command you want to run')
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Arguments to be passed along to the command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $input instanceof ArgvInput) {
            throw new RuntimeException();
        }

        $io = new SymfonyStyle($input, $output);
        $identifier = $input->getArgument('project');

        try {
            $environment = (new EnvironmentFinder)->find($identifier);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($environment->isOrphaned()) {
            $io->error('@todo');

            return Command::FAILURE;
        }

        try {
            chdir($environment->project->path);
        } catch (\Exception $e) {
            throw new \RuntimeException("Could not switch to project directory '{$environment->project->path}'", 0, $e);
        }

        $io->writeln("<info>Changed current directory to {$environment->project->path}.</info>");

        $newInput = new ArgvInput(['eznv', ...$this->removeFirstOccurrences(
            $input->getRawTokens(),
            [$this->getName(), $input->getArgument('project')]
        )]);

        return $this->getApplication()->run($newInput, $output);
    }

    private function createSubcommandInput(CompletionInput $input): CompletionInput
    {
        $tokens = preg_split('{\s+}', $input->__toString());

        if ($tokens === false) {
            throw new RuntimeException();
        }

        $tokens = $this->removeFirstOccurrences($tokens, [$this->getName(), $input->getArgument('project'), '|']);

        return CompletionInput::fromTokens($tokens, 2);
    }

    private function removeFirstOccurrences(array $array, array $removals): array
    {
        foreach ($removals as $removal) {
            $index = array_search($removal, $array, true);

            if (false !== $index) {
                unset($array[$index]);
            }
        }

        return $array;
    }

    private function suggestCommandNames(): array
    {
        return array_values(
            array_filter(
                array_map(
                    static fn (Command $command) => $command->isHidden() ? null : $command->getName(),
                    $this->getApplication()->all()
                ),
                static fn (?string $cmd) => $cmd !== null
            )
        );
    }

    private function suggestProjects(CompletionInput $input): array
    {
        $currentValue = $input->getCompletionValue();
        $suggestions = [];
        $environments = (new EnvironmentFinder)->findAll();

        if ('' === $currentValue) {
            foreach ($environments as $environment) {
                // The user hasn't typed anything yet so let's just provide name for a clean but descriptive identifier.
                // @todo Does it make more sense to provide name or path?
                $suggestions[] = $environment->project->name;
            }

            return $suggestions;
        }

        foreach ($environments as $environment) {
            // We are skipping hash intentionally - easier to just use id.
            foreach (['path', 'name', 'id'] as $prop) {
                if (str_starts_with($environment->project->{$prop}, $currentValue)) {
                    $suggestions[] = $environment->project->{$prop};
                }
            }
        }

        return $suggestions;
    }
}