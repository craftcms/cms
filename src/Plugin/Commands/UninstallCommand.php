<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;

final class UninstallCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    protected $signature = 'craft:plugin:uninstall {handle?} {--all} {--force}';

    protected $description = 'Uninstalls a plugin.';

    protected $aliases = ['plugin/uninstall'];

    private Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->uninstallAll();
        }

        $handle = $this->getHandle(fn (array $info) => $info['isInstalled'] && ($info['isEnabled'] || $this->option('force')));
        $plugin = $this->plugins->isPluginInstalled($handle);

        if (! $plugin) {
            $this->components->error("No plugin is installed with the handle `$handle`.");

            return self::FAILURE;
        }

        $this->uninstallPluginByHandle($handle);

        return self::SUCCESS;
    }

    private function uninstallAll(): int
    {
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            // filter out the ones that are uninstalled/disabled
            ->filter(fn (array $info) => $info['isInstalled'] && ($info['isEnabled'] || $this->option('force')))
            ->keys();

        if ($pluginInfo->isEmpty()) {
            if ($this->option('force')) {
                $this->components->info('There aren’t any installed plugins present.');
            } else {
                $this->components->info('There aren’t any installed and enabled plugins present.');
            }

            return self::SUCCESS;
        }

        $pluginInfo->each(function (string $handle) {
            $this->uninstallPluginByHandle($handle);
            $this->newLine();
        });

        return self::SUCCESS;
    }

    private function uninstallPluginByHandle(string $handle): void
    {
        $this->components->task("Uninstalling $handle", fn () => $this->plugins->uninstallPlugin($handle, $this->option('force')));
    }
}
