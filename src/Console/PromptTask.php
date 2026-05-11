<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Closure;
use Laravel\Prompts\Output\ConsoleOutput;
use Laravel\Prompts\task;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\task;

class PromptTask
{
    public static function run(
        string $label,
        Closure $callback,
        ?int $limit = null,
        bool $keepSummary = false,
        ?string $subLabel = null,
        ?OutputInterface $output = null,
    ): mixed {
        if (! function_exists(task::class) || ! function_exists('pcntl_fork')) {
            $output ??= new ConsoleOutput;

            $output->writeln(str($label)->finish('...')->toString());

            return $callback(new FallbackPromptLogger($output));
        }

        return task($label, $callback, $limit, $keepSummary, $subLabel);
    }
}
