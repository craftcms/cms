<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait CraftCommand
{
    public function removeCraftGroup(): void
    {
        if (empty($this->signature)) {
            $this->signature = $this->name;
        }

        $this->signature = Str::after($this->signature, 'craft:');

        parent::__construct();
    }

    protected function ensureProjectConfigFileExists(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (! $projectConfig->writeYamlAutomatically) {
            return;
        }

        if ($projectConfig->getDoesExternalConfigExist()) {
            return;
        }

        $this->outputComponents()->info('Generating project config files from the loaded project config ... ');
        $projectConfig->regenerateExternalConfig();
        $this->outputComponents()->success('done');
    }
}
