<?php

namespace CraftCms\Cms\Section;

use CraftCms\Cms\Section\Commands\CreateCommand;
use CraftCms\Cms\Section\Commands\DeleteCommand;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class SectionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            CreateCommand::class,
            DeleteCommand::class,
        ]);

        Event::listen(SiteDeleted::class, function (SiteDeleted $event): void {
            if (ProjectConfig::isApplyingExternalChanges()) {
                return;
            }

            Sections::pruneDeletedSite($event);
        });
    }
}
