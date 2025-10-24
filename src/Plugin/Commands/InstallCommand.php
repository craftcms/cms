<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\select;

final class InstallCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    protected $signature = 'craft:plugin:install {handle?} {edition?} {--all}';

    protected $description = 'Installs a plugin.';

    protected $aliases = ['plugin/install'];

    protected Plugins $plugins;

    public function handle(Plugins $plugins): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;

        if ($this->option('all')) {
            return $this->installAll();
        }

        $handle = $this->getHandle(fn (array $info) => ! $info['isInstalled']);
        $edition = $this->argument('edition');

        if ($this->plugins->isPluginInstalled($handle)) {
            return $this->switchEdition($handle, $edition);
        }

        if ($edition === null && $this->input->isInteractive()) {
            try {
                $info = $this->plugins->getPluginInfo($handle);
            } catch (InvalidPluginException $e) {
                $this->components->error($e->getMessage());

                return self::FAILURE;
            }

            /** @var class-string<PluginInterface> $class */
            $class = $info['class'];
            $editions = $class::editions();

            if (count($editions) > 1) {
                $edition = select(
                    label: 'Which edition?',
                    options: $editions,
                    default: reset($editions),
                    validate: fn ($value) => in_array($value, $editions) ? null : 'The edition must be one of: '.implode(', ', $editions),
                );
            }
        }

        $this->installPluginByHandle($handle, $edition);

        return self::SUCCESS;
    }

    private function installAll(): int
    {
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            ->filter(fn (array $info) => ! $info['isInstalled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any uninstalled plugins present.');

            return self::SUCCESS;
        }

        $pluginInfo->each(function (string $handle) {
            $this->installPluginByHandle($handle);
            $this->newLine();
        });

        return self::SUCCESS;
    }

    private function switchEdition(string $handle, ?string $edition): int
    {
        /** @var PluginInterface $plugin */
        $plugin = $this->plugins->getPlugin($handle);

        if ($edition === null || $edition === $plugin->edition) {
            $this->components->warn(sprintf(
                "%s%s is already installed.\n",
                $plugin->name,
                $edition !== null ? " ($edition)" : ''
            ));

            return self::FAILURE;
        }

        try {
            $this->plugins->switchEdition($handle, $edition);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("$plugin->name switched to the `$edition` edition.");

        return self::SUCCESS;
    }

    private function installPluginByHandle(string $handle, ?string $edition = null): void
    {
        $this->components->task("Installing $handle", fn () => $this->plugins->installPlugin($handle, $edition));
    }
}
