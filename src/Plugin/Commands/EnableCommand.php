<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;

final class EnableCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    #[\Override]
    protected $signature = 'craft:plugin:enable {handle?} {--all}';

    #[\Override]
    protected $description = 'Enables a plugin.';

    #[\Override]
    protected $aliases = ['plugin/enable'];

    private Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->enableAll();
        }

        $handle = $this->getHandle(fn (array $info) => $info['isInstalled'] && ! $info['isEnabled']);

        $this->enablePluginByHandle($handle);

        return self::SUCCESS;
    }

    private function enableAll(): int
    {
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            // filter out the ones that are uninstalled/enabled
            ->filter(fn (array $info) => $info['isInstalled'] && ! $info['isEnabled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any installed and disabled plugins present.');

            return self::SUCCESS;
        }

        foreach ($pluginInfo as $handle) {
            $this->enablePluginByHandle($handle);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function enablePluginByHandle(string $handle): void
    {
        $this->components->task("Enabling $handle", fn () => $this->plugins->enablePlugin($handle));
    }
}
