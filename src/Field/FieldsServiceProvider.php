<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Field\Commands\DeleteFieldsCommand;
use CraftCms\Cms\Field\Commands\FieldsAutoMergeCommand;
use CraftCms\Cms\Field\Commands\FieldsMergeCommand;
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
    }
}
