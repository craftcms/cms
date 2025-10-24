<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\Events\AddingItem;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\RemovingItem;
use CraftCms\Cms\ProjectConfig\Events\UpdatingItem;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

final class ApplyCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:project-config:apply {--force : Whether every entry change should be force-applied.}';

    protected $description = 'Applies project config file changes.';

    protected $aliases = ['project-config/apply', 'project-config:sync', 'project-config/sync', 'pc:apply', 'pc/apply', 'pc:sync', 'pc/sync'];

    /**
     * @var int Counter of the total paths that have been processed.
     */
    private int $pathCount = 0;

    /**
     * @var array The config paths that are currently being processed.
     */
    private array $processingPaths;

    /**
     * @var array The config paths that have finished being processed.
     */
    private array $completedPaths = [];

    public function handle(Updates $updates, ProjectConfig $projectConfig, Plugins $plugins): int
    {
        if ($updates->isCraftUpdatePending() || $updates->isPluginUpdatePending()) {
            $this->components->error('Craft has pending migrations. Please run `craft migrate:all` first.');

            return self::FAILURE;
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

        // Do we need to create a new config file?
        if (! $projectConfig->getDoesExternalConfigExist()) {
            $this->components->warn('No project config files found. Generating them from internal config ...');
            $projectConfig->regenerateExternalConfig();

            return self::SUCCESS;
        }

        // Install new plugins
        $loadedConfigPlugins = array_keys($projectConfig->get(ProjectConfig::PATH_PLUGINS) ?? []);
        $yamlPlugins = array_keys($projectConfig->get(ProjectConfig::PATH_PLUGINS, true) ?? []);

        if (! $this->installPlugins($plugins, array_diff($yamlPlugins, $loadedConfigPlugins))) {
            $this->components->error('Aborting config apply process.');

            return self::FAILURE;
        }

        $this->components->info('Applying changes from your project config files');

        $forceUpdate = $projectConfig->forceUpdate;
        $projectConfig->forceUpdate = $this->option('force');

        if (! $this->option('quiet')) {
            $this->processingPaths = [];

            Event::listen(AddingItem::class, [$this, 'onStartProcessingItem']);
            Event::listen(ItemAdded::class, [$this, 'onFinishProcessingItem']);
            Event::listen(RemovingItem::class, [$this, 'onStartProcessingItem']);
            Event::listen(ItemRemoved::class, [$this, 'onFinishProcessingItem']);
            Event::listen(UpdatingItem::class, [$this, 'onStartProcessingItem']);
            Event::listen(ItemUpdated::class, [$this, 'onFinishProcessingItem']);
        }

        $projectConfig->applyExternalChanges();

        $projectConfig->forceUpdate = $forceUpdate;

        $this->components->success('Finished applying changes');

        // Uninstall plugins
        $this->uninstallPlugins($plugins, array_diff($loadedConfigPlugins, $yamlPlugins));

        return self::SUCCESS;
    }

    public function onStartProcessingItem(ConfigEvent $event): void
    {
        if (isset($this->processingPaths[$event->path]) || isset($this->completedPaths[$event->path])) {
            return;
        }

        // Are we in the middle of processing another path(s)?
        $otherPaths = count($this->processingPaths);
        $indent = '';
        if ($otherPaths !== 0) {
            $indent = str_repeat('..', $otherPaths);
        }

        $label = match ($event::class) {
            ItemAdded::class => 'adding',
            ItemRemoved::class => 'removing',
            ItemUpdated::class => 'updating',
            default => '',
        };

        $this->components->twoColumnDetail($indent." ⇂ {$label} <fg=cyan>{$event->path}</>");

        $this->processingPaths[$event->path] = ++$this->pathCount;
    }

    public function onFinishProcessingItem(ConfigEvent $event): void
    {
        if (! isset($this->processingPaths[$event->path])) {
            return;
        }

        unset($this->processingPaths[$event->path]);
        $this->completedPaths[$event->path] = true;
    }

    private function installPlugins(Plugins $plugins, array $handles): bool
    {
        foreach ($handles as $handle) {
            $this->components->task(
                "⇂ Installing plugin <fg=cyan>$handle</>",
                fn () => $plugins->installPlugin($handle),
            );
        }

        return true;
    }

    private function uninstallPlugins(Plugins $plugins, array $handles): bool
    {
        foreach ($handles as $handle) {
            $this->components->task(
                "⇂ Uninstalling plugin <fg=cyan>$handle</>",
                fn () => $plugins->uninstallPlugin($handle, true),
            );
        }

        return true;
    }
}
