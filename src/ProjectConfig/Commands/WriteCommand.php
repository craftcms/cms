<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;

class WriteCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:write';

    #[\Override]
    protected $description = 'Writes out the currently-loaded project config as YAML files to the `config/project/` folder, discarding any pending YAML changes.';

    #[\Override]
    protected $aliases = ['project-config/write', 'pc:write', 'pc/write'];

    public function handle(ProjectConfig $projectConfig): void
    {
        $this->components->task(
            'Writing out project config files',
            fn () => $projectConfig->regenerateExternalConfig(),
        );
    }
}
