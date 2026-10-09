<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Laravel\Prompts\Support\Logger;

/**
 * @mixin Command
 */
trait CraftCommand
{
    /** @return string[] */
    public function getAliases(): array
    {
        return array_map(
            fn (string $alias) => Str::start($alias, 'craft:'),
            parent::getAliases(),
        );
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

        PromptTask::run('Generating project config files from the loaded project config', function (Logger $logger) use ($projectConfig) {
            $projectConfig->regenerateExternalConfig();
            $logger->success('Done.');
        }, output: $this->output);
    }
}
