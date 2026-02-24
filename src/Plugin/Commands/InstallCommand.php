<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Api;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\select;

final class InstallCommand extends Command
{
    use CraftCommand;
    use PromptsForMissingHandle;

    #[\Override]
    protected $signature = 'craft:plugin:install {handle?} {edition?} {--all}';

    #[\Override]
    protected $description = 'Installs a plugin.';

    #[\Override]
    protected $aliases = ['plugin/install'];

    protected Plugins $plugins;

    private Api $api;

    public function handle(Plugins $plugins, Api $api): int
    {
        $this->ensureProjectConfigFileExists();

        $this->plugins = $plugins;
        $this->api = $api;

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

        $this->refreshPluginLicenseInfo();

        return self::SUCCESS;
    }

    private function installAll(): int
    {
        $pluginInfo = $this->plugins
            ->getAllPluginInfo()
            ->reject(fn (array $info): bool => (bool) $info['isInstalled'])
            ->keys();

        if ($pluginInfo->isEmpty()) {
            $this->components->info('There aren’t any uninstalled plugins present.');

            return self::SUCCESS;
        }

        $pluginInfo->each(function (string $handle) {
            $this->installPluginByHandle($handle);
            $this->newLine();
        });

        $this->refreshPluginLicenseInfo();

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

    private function refreshPluginLicenseInfo(): void
    {
        $this->components->task('Updating license info', function () {
            try {
                $this->api->request('GET', 'cms-licenses', [
                    'query' => ['include' => 'plugins'],
                ]);
            } catch (Throwable) {
                // do nothing
            }
        });
    }
}
