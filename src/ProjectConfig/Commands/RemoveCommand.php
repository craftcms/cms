<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

/**
 * Removes a project config value.
 *
 * Example:
 * ```
 * php craft project-config:remove some.nested.key
 * ```
 *
 * ::: danger
 * This should only be used when the equivalent change is not possible through the control panel or other Craft APIs. By directly modifying project config values, you are bypassing all validation and can easily destabilize configuration.
 * :::
 *
 * As with [set](#project-config-set), removing values only updates the root `dateModified` key when using the [`--update-timestamp` flag](#project-config-set-options). If you do not include this flag, you must run `project-config/touch` before changes will be detected or applied in other environments!
 */
final class RemoveCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:remove
        {path}
        {--message= : A message describing the changes.}
        {--update-timestamp : Whether the `dateModified` value should be updated}
        {--force : Whether every entry change should be force-applied.}
    ';

    #[\Override]
    protected $description = 'Removes a project config value.';

    #[\Override]
    protected $aliases = ['project-config/remove', 'pc:remove', 'pc/remove'];

    public function handle(): int
    {
        return $this->call(SetCommand::class, [
            'path' => $this->argument('path'),
            'value' => null,
            '--message' => $this->option('message'),
            '--update-timestamp' => $this->option('update-timestamp'),
            '--force' => $this->option('force'),
        ]);
    }
}
