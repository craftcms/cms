<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;

/**
 * Checks project config schema compatibility with installed Craft and enabled plugins.
 *
 * Requires database access to an existing Craft installation, even when `--require-yaml=0` is passed.
 * Does not apply project config or migrations.
 *
 * @since 5.12.0
 */
class CheckCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:project-config:check {--require-yaml=1 : Whether the check should fail if `project.yaml` is missing.}';

    #[\Override]
    protected $description = 'Checks project config schema compatibility with installed Craft and enabled plugins.';

    #[\Override]
    protected $aliases = ['project-config/check', 'pc:check', 'pc/check'];

    public function handle(ProjectConfig $projectConfig, Plugins $plugins): int
    {
        if (! Cms::isInstalled()) {
            $this->components->error('This check requires an existing Craft installation.');

            return self::FAILURE;
        }

        if (! $projectConfig->getDoesExternalConfigExist()) {
            if (! filter_var($this->option('require-yaml'), FILTER_VALIDATE_BOOL)) {
                $this->components->info('Project config file `project.yaml` was not found. Schema compatibility check skipped.');

                return self::SUCCESS;
            }

            $this->components->error('Project config file `project.yaml` was not found. Schema compatibility could not be checked.');

            return self::FAILURE;
        }

        if ($projectConfig->getHadFileWriteIssues()) {
            $this->components->error('Resolve project config file-write errors before checking schema compatibility. Craft is currently using internal config instead of YAML.');

            return self::FAILURE;
        }

        foreach (array_keys($projectConfig->get(ProjectConfig::PATH_PLUGINS) ?? []) as $handle) {
            if ($plugins->isPluginEnabled($handle) && $plugins->getPlugin($handle) === null) {
                $this->components->error("Enabled plugin \"$handle\" could not be loaded. Check its Composer package and plugin initialization errors before checking schema compatibility.");

                return self::FAILURE;
            }
        }

        $issues = [];

        if (! $projectConfig->getAreConfigSchemaVersionsCompatible($issues)) {
            $this->components->warn('Your project config files were created for different versions of Craft and/or plugins than what’s currently installed.');

            foreach ($issues as $issue) {
                $this->components->warn(implode(' ', [
                    '<fg=red>'.$issue['cause'].'</>',
                    'is installed with schema version of',
                    '<fg=red>'.$issue['existing'].'</>',
                    'while',
                    '<fg=red>'.$issue['incoming'].'</>',
                    'was expected.',
                ]));
            }

            $this->components->warn('Try running `composer install` from your terminal to resolve.');

            return self::FAILURE;
        }

        $this->components->success('Project config schema versions are compatible with installed Craft and enabled plugins.');

        return self::SUCCESS;
    }
}
