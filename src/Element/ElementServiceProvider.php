<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Activity\DraftActivity;
use CraftCms\Cms\Activity\ElementWriteActivity;
use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;
use CraftCms\Cms\Element\BulkOp\Events\BulkOpCompleted;
use CraftCms\Cms\Element\Commands\DeleteAllOfTypeCommand;
use CraftCms\Cms\Element\Commands\DeleteCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAddressesCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAllCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveAssetsCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveEntriesCommand;
use CraftCms\Cms\Element\Commands\Resave\ResaveUsersCommand;
use CraftCms\Cms\Element\Commands\RestoreCommand;
use CraftCms\Cms\Element\Commands\SyncEloquentElementsCommand;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class ElementServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // From register(), so Eloquent element types' routes come before the Yii adapter's
        // control panel catch-all, which it also adds once the app has booted
        $this->app->booted(fn () => $this->app->make(EloquentElements::class)->registerRoutes());
    }

    public function boot(): void
    {
        Event::subscribe(DraftActivity::class);
        Event::subscribe(ElementWriteActivity::class);
        Event::listen(BulkOpCompleted::class, fn (BulkOpCompleted $event) => app(BulkOpDeferrals::class)->replay($event->key));

        app()->terminating(fn () => app(BulkOpDeferrals::class)->persistPending());

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            DeleteCommand::class,
            DeleteAllOfTypeCommand::class,
            RestoreCommand::class,
            SyncEloquentElementsCommand::class,
            ResaveAllCommand::class,
            ResaveEntriesCommand::class,
            ResaveAssetsCommand::class,
            ResaveAddressesCommand::class,
            ResaveUsersCommand::class,
        ]);
    }
}
