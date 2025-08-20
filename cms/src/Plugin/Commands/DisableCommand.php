<?php

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;
use Throwable;

final class DisableCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    protected $signature = 'craft:plugin:disable {handle?} {--all}';

    protected $description = 'Disables a plugin.';

    private Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->disableAll();
        }

        $handle = $this->getHandle(fn (array $info) => $info['isInstalled'] && $info['isEnabled']);

        return $this->disablePluginByHandle($handle);
    }

    private function disableAll(): int
    {
        // get all plugins’ info
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            // filter out the ones that are uninstalled/disabled
            ->filter(fn (array $info) => $info['isInstalled'] && $info['isEnabled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any installed and disabled plugins present.');

            return self::SUCCESS;
        }

        // disable them one by one
        foreach ($pluginInfo as $handle) {
            $this->disablePluginByHandle($handle);
        }

        return self::SUCCESS;
    }

    private function disablePluginByHandle(string $handle): int
    {
        $this->components->info("Disabling $handle...");
        $start = microtime(true);

        try {
            $success = $this->plugins->disablePlugin($handle);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (! $success) {
                $this->components->error("failed to disable $handle".(isset($e) ? ": {$e->getMessage()}" : '.'));

                return self::FAILURE;
            }
        }

        $time = sprintf('%.3f', microtime(true) - $start);

        $this->components->success("Disabled $handle successfully (time: {$time}s)");

        return self::SUCCESS;
    }
}
