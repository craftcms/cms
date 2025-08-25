<?php

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Throwable;

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

        return $this->disablePluginByHandle($handle);
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

    private function disablePluginByHandle(string $handle): int
    {
        new TwoColumnDetail($this->getOutput())->render(
            "Disabling $handle",
        );

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

        $time = number_format((microtime(true) - $start) * 1000);

        new TwoColumnDetail($this->getOutput())->render(
            "<fg=green>Disabled $handle successfully</>",
            "<fg=gray>{$time}ms</>"
        );

        return self::SUCCESS;
    }
}
