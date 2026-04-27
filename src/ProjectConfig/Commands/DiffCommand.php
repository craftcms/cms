<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Console\Command;

class DiffCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:diff
        {--invert : Whether to treat the loaded project config as the source of truth, instead of the YAML files.}
    ';

    #[\Override]
    protected $description = 'Outputs a diff of the pending project config YAML changes.';

    #[\Override]
    protected $aliases = ['project-config/diff', 'pc:diff', 'pc/diff'];

    public function handle(): int
    {
        $diff = ProjectConfigHelper::diff($this->option('invert'));

        if ($diff === '') {
            $this->components->success('No pending project config YAML changes.');

            return self::SUCCESS;
        }

        foreach (explode("\n", $diff) as $line) {
            match ($line[0] ?? '') {
                '-' => $this->line("<fg=green>{$line}</>"),
                '+' => $this->line("<fg=red>{$line}</>"),
                default => $this->line($line),
            };
        }

        return self::SUCCESS;
    }
}
