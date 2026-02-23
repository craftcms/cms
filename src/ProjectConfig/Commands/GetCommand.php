<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Yaml\Yaml;

/**
 * Outputs a project config value.
 *
 * Example:
 * ```
 * php craft project-config:get system.edition
 * ```
 *
 * The “path” syntax used here may be composed of directory and filenames (within your `config/project` folder), YAML object keys (including UUIDs for many Craft resources), and integers (referencing numerically-indexed arrays), joined by a dot (`.`): `path.to.nested.array.0.property`.
 */
final class GetCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:get
        {path}
        {--external : Whether to pull values from the project config YAML files instead of the loaded config.}
    ';

    #[\Override]
    protected $description = 'Outputs a project config value.';

    #[\Override]
    protected $aliases = ['project-config/get', 'pc:get', 'pc/get'];

    public function handle(ProjectConfig $projectConfig): void
    {
        $value = $projectConfig->get($this->argument('path'), $this->option('external'));

        $this->components->info(Yaml::dump($value));
    }
}
