<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Field\Commands\DeleteFieldsCommand;
use CraftCms\Cms\Field\Commands\FieldsAutoMergeCommand;
use CraftCms\Cms\Field\Commands\FieldsMergeCommand;
use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider
{
    public function boot(ProjectConfig $projectConfig, CustomFieldIdeHelperGenerator $generator): void
    {
        $this->commands([
            DeleteFieldsCommand::class,
            FieldsMergeCommand::class,
            FieldsAutoMergeCommand::class,
        ]);

        $this->registerIdeHelperListeners($projectConfig, $generator);
    }

    private function registerIdeHelperListeners(ProjectConfig $projectConfig, CustomFieldIdeHelperGenerator $generator): void
    {
        $regenerate = fn () => once(fn () => $generator->generate());

        $paths = [
            ProjectConfig::PATH_ENTRY_TYPES.'.{uid}',
            ProjectConfig::PATH_FIELDS.'.{uid}',
            ProjectConfig::PATH_VOLUMES.'.{uid}',
            ProjectConfig::PATH_USER_FIELD_LAYOUTS,
        ];

        foreach ($paths as $path) {
            $projectConfig
                ->onAdd($path, $regenerate)
                ->onUpdate($path, $regenerate)
                ->onRemove($path, $regenerate);
        }
    }
}
