<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;

final class DisableCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    protected $signature = 'craft:plugin:disable {handle?} {--all}';

    protected $description = 'Disables a plugin.';

    protected $aliases = ['plugin/disable'];

    private Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->disableAll();
        }

        $handle = $this->getHandle(fn (array $info) => $info['isInstalled'] && $info['isEnabled']);

        $this->disablePluginByHandle($handle);

        return self::SUCCESS;
    }

    private function disableAll(): int
    {
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            // filter out the ones that are uninstalled/disabled
            ->filter(fn (array $info) => $info['isInstalled'] && $info['isEnabled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any installed and disabled plugins present.');

            return self::SUCCESS;
        }

        foreach ($pluginInfo as $handle) {
            $this->disablePluginByHandle($handle);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function disablePluginByHandle(string $handle): void
    {
        $this->components->task("Disabling $handle", fn () => $this->plugins->disablePlugin($handle));
    }
}
