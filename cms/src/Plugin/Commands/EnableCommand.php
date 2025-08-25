<?php

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Throwable;

final class EnableCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    protected $signature = 'craft:plugin:enable {handle?} {--all}';

    protected $description = 'Enables a plugin.';

    private Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->enableAll();
        }

        $handle = $this->getHandle(fn (array $info) => $info['isInstalled'] && ! $info['isEnabled']);

        return $this->enablePluginByHandle($handle);
    }

    private function enableAll(): int
    {
        // get all plugins’ info
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            // filter out the ones that are uninstalled/enabled
            ->filter(fn (array $info) => $info['isInstalled'] && ! $info['isEnabled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any installed and disabled plugins present.');

            return self::SUCCESS;
        }

        // enable them one by one
        foreach ($pluginInfo as $handle) {
            $this->enablePluginByHandle($handle);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function enablePluginByHandle(string $handle): int
    {
        with(new TwoColumnDetail($this->getOutput()))->render(
            "Enabling $handle",
        );

        $start = microtime(true);

        try {
            $success = $this->plugins->enablePlugin($handle);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (! $success) {
                $this->components->error("failed to enable $handle".(isset($e) ? ": {$e->getMessage()}" : '.'));

                return self::FAILURE;
            }
        }

        $time = number_format((microtime(true) - $start) * 1000);

        with(new TwoColumnDetail($this->getOutput()))->render(
            "<fg=green>Enabled $handle successfully</>",
            "<fg=gray>{$time}ms</>"
        );

        return self::SUCCESS;
    }
}
