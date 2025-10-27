<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Sets a project config value.
 *
 * Example:
 * ```
 * php craft project-config:set some.nested.key
 * ```
 *
 * See [get](#project-config-get) for the accepted key formats.
 *
 * ::: danger
 * This should only be used when the equivalent change is not possible through the control panel or other Craft APIs. By directly modifying project config values, you are bypassing all validation and can easily destabilize configuration.
 * :::
 *
 * Values are updated in the database *and* in your local YAML files, but the root `dateModified` project config property is only touched when using the [`--update-timestamp` flag](#project-config-set-options). If you do not update the timestamp along with the value, the change may not be detected or applied in other environments!
 */
final class SetCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:project-config:set
        {path} {value?}
        {--message= : A message describing the changes.}
        {--update-timestamp : Whether the `dateModified` value should be updated}
        {--force : Whether every entry change should be force-applied.}
    ';

    protected $description = 'Sets a project config value.';

    protected $aliases = ['project-config/set', 'pc:set', 'pc/set'];

    public function handle(ProjectConfig $projectConfig): int
    {
        try {
            $parsedValue = Yaml::parse($this->argument('value') ?? '');
        } catch (ParseException $e) {
            $this->components->error('Input value must be valid YAML.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $path = $this->argument('path');

        $projectConfig->set(
            $path,
            $parsedValue,
            $this->option('message'),
            $this->option('update-timestamp'),
            $this->option('force'),
        );

        $value = $projectConfig->get($path);

        if ($value === null) {
            $this->components->info("Project config path <fg=cyan>{$path}</> has been removed.");

            return self::SUCCESS;
        }

        $dumpedValue = Yaml::dump($value);
        $this->info("Project config path <fg=cyan>{$path}</> has been set to:");
        $this->info($dumpedValue);

        return self::SUCCESS;
    }
}
