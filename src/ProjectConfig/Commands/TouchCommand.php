<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Console\Command;

final class TouchCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:touch';

    #[\Override]
    protected $description = 'Updates the `dateModified` value in `config/project/project.yaml`, attempting to resolve a Git conflict for it.';

    #[\Override]
    protected $aliases = ['project-config/touch', 'pc:touch', 'pc/touch'];

    public function handle(): void
    {
        ProjectConfigHelper::touch($time = now()->getTimestamp());

        $this->components->success("The dateModified value in project.yaml is now set to $time.");
    }
}
