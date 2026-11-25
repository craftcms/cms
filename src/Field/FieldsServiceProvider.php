<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Field\Commands\DeleteFieldsCommand;
use CraftCms\Cms\Field\Commands\FieldsAutoMergeCommand;
use CraftCms\Cms\Field\Commands\FieldsMergeCommand;
use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use CraftCms\Cms\Field\Listeners\FieldReferenceEventSubscriber;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class FieldsServiceProvider extends ServiceProvider
{
    public function boot(ProjectConfig $projectConfig): void
    {
        $this->commands([
            DeleteFieldsCommand::class,
            FieldsMergeCommand::class,
            FieldsAutoMergeCommand::class,
        ]);

        Event::subscribe(FieldReferenceEventSubscriber::class);

        $this->registerIdeHelperListeners($projectConfig);
    }

    /**
     * Regenerates the IDE helper once the request or command that changed the content model has finished, as
     * project config flushes its own changes then. Regenerating on the first change would miss the rest, such
     * as the other entry types and fields a project config apply adds after it.
     */
    private function registerIdeHelperListeners(ProjectConfig $projectConfig): void
    {
        $stale = false;
        $markStale = function () use (&$stale): void {
            $stale = true;
        };

        Event::listen([RequestHandled::class, CommandFinished::class], function () use (&$stale): void {
            if (! $stale) {
                return;
            }

            $stale = false;
            $this->app->make(CustomFieldIdeHelperGenerator::class)->generate();
        });

        $paths = [
            ProjectConfig::PATH_ENTRY_TYPES.'.{uid}',
            ProjectConfig::PATH_FIELDS.'.{uid}',
            ProjectConfig::PATH_VOLUMES.'.{uid}',
            ProjectConfig::PATH_USER_FIELD_LAYOUTS,
        ];

        foreach ($paths as $path) {
            $projectConfig
                ->onAdd($path, $markStale)
                ->onUpdate($path, $markStale)
                ->onRemove($path, $markStale);
        }
    }
}
