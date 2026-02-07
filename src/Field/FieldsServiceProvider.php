<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Field\Commands\DeleteFieldsCommand;
use CraftCms\Cms\Field\Commands\FieldsAutoMergeCommand;
use CraftCms\Cms\Field\Commands\FieldsMergeCommand;
use CraftCms\Cms\Field\Events\FieldLayoutSaved;
use CraftCms\Cms\Field\Events\FieldSaved;
use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class FieldsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            DeleteFieldsCommand::class,
            FieldsMergeCommand::class,
            FieldsAutoMergeCommand::class,
        ]);

        $this->registerIdeHelperListeners();
    }

    private function registerIdeHelperListeners(): void
    {
        $regenerate = fn () => app(CustomFieldIdeHelperGenerator::class)->generate();

        Event::listen(FieldLayoutSaved::class, $regenerate);
        Event::listen(FieldSaved::class, $regenerate);
    }
}
