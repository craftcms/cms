<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;

class RebuildCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:rebuild';

    #[\Override]
    protected $description = 'Rebuilds the project config.';

    #[\Override]
    protected $aliases = ['project-config/rebuild', 'pc:rebuild', 'pc/rebuild'];

    public function handle(ProjectConfig $projectConfig): void
    {
        if ($projectConfig->writeYamlAutomatically && ! $projectConfig->getDoesExternalConfigExist()) {
            $this->components->warn('No project config files found. Generating them from internal config.');

            $projectConfig->regenerateExternalConfig();
        }

        $this->components->task(
            'Rebuilding the project config from the current state',
            fn () => $projectConfig->rebuild(),
        );
    }
}
