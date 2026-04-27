<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use CraftCms\Cms\Element\Events\DefineResaveCommands;
use CraftCms\Cms\Support\Str;
use Override;

class ResaveAllCommand extends ResaveCommand
{
    #[Override]
    protected $signature = 'craft:resave:all'.self::DEFAULT_OPTIONS;

    #[Override]
    protected $description = 'Runs all resave commands.';

    #[Override]
    protected $aliases = ['resave/all'];

    public function handle(): int
    {
        $commands = [
            'craft:resave:entries',
            'craft:resave:assets',
            'craft:resave:addresses',
            'craft:resave:users',
        ];

        event($event = new DefineResaveCommands);

        foreach ($event->commands as $commandName => $command) {
            if (! $this->getApplication()->has($commandName)) {
                continue;
            }

            $commands[] = $commandName;
        }

        $commands = array_values(array_unique($commands));

        $passedOptions = $this->collectPassedOptions();

        $exitCode = self::SUCCESS;

        foreach ($commands as $commandName) {
            $this->components->info("Running <fg=cyan;options=bold>$commandName</>");

            $result = $this->call($commandName, $passedOptions);

            if ($result !== self::SUCCESS) {
                $exitCode = self::FAILURE;
            }
        }

        return $exitCode;
    }

    /**
     * Returns the options that were explicitly passed by the user.
     *
     * @return array<string, mixed>
     */
    private function collectPassedOptions(): array
    {
        $passed = [];

        foreach ($this->getDefinition()->getOptions() as $option) {
            $name = $option->getName();

            if (! $this->hasOption($name)) {
                continue;
            }

            $value = $this->option($name);

            if ($option->isArray()) {
                $value = $this->normalizeArrayOptionValue($name, $value);
            }

            if ($value === $option->getDefault()) {
                continue;
            }

            $passed["--$name"] = $value;
        }

        return $passed;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeArrayOptionValue(string $name, mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($this->input->hasParameterOption("--$name", true)) {
            $rawValue = $this->input->getParameterOption("--$name", false, true);

            if (is_string($rawValue)) {
                return Str::of($rawValue)
                    ->explode(',')
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return collect($value)
            ->flatMap(fn (mixed $item) => is_string($item) ? Str::of($item)->explode(',')->all() : [$item])
            ->filter(fn (mixed $item) => $item !== '')
            ->values()
            ->all();
    }
}
